<?php

namespace Kinintel\Test\Services\Util\Analysis\TextAnalysis\VectorEmbedding;

use Exception;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding\OpenAIEmbeddingService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Authentication\WebService\HTTPHeaderAuthenticationCredentials;

include_once "autoloader.php";
class OpenAIEmbeddingServiceTest extends TestBase {

    /**
     * @var HttpRequestDispatcher
     */
    private $requestDispatcher;

    /**
     * @var AuthenticationCredentialsService
     */
    private $credentialsService;

    private OpenAIEmbeddingService $embeddingService;

    private const OPENAI_API_KEY = "MY_APIKEY";

    protected function setUp(): void {
        $this->credentialsService = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsService::class);

        $credentials = MockObjectProvider::instance()->getMockInstance(HTTPHeaderAuthenticationCredentials::class);
        $credentials->returnValue("getAuthParams", ["Authorization" => "Bearer ". self::OPENAI_API_KEY]);

        $credInstance = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsInstance::class);
        $credInstance->returnValue("returnCredentials", $credentials);

        $this->credentialsService->returnValue("getCredentialsInstanceByKey", $credInstance);
//        $this->requestDispatcher = MockObjectProvider::instance()->getMockInstance(HttpRequestDispatcher::class);
        $this->requestDispatcher = Container::instance()->get(HttpRequestDispatcher::class);
        $this->embeddingService = new OpenAIEmbeddingService($this->requestDispatcher, $this->credentialsService);
    }

    /**
     * @nontravis
     * @return void
     */
    public function testGetEmbedding(){
        $target = "This is target sentence which we should go for";
        $goodStr = "A sentence to aim at";
        $badStr = "A sentence to avoid looking at";

        $exTargetEmbed = [1, 0, 0];
        $exGoodEmbed = [1/sqrt(2), 1/sqrt(2), 0];
        $exBadEmbed = [0,0,1];

        $mockDispatcher = MockObjectProvider::instance()->getMockInstance(HttpRequestDispatcher::class);
        $mockedEmbeddingService = new OpenAIEmbeddingService($mockDispatcher, $this->credentialsService);

        // Turns a list of numerical embedding vectors into a Response obj
        $toBody = function($embeds){
            $mappedEmbeds = array_map(fn($embed) => ["embedding" => $embed], $embeds);
            $out = [
                "data" => $mappedEmbeds
            ];

            return new \GuzzleHttp\Psr7\Response(body: json_encode($out));
        };

        $mockDispatcher->returnValue("dispatch", $toBody([$exTargetEmbed]));
        $targetEmbed = $mockedEmbeddingService->embedString($target);

        $mockDispatcher->returnValue("dispatch", $toBody( [$exGoodEmbed, $exBadEmbed]));
        [$goodEmbed, $badEmbed] = $mockedEmbeddingService->embedStrings([$goodStr, $badStr]);

        $this->assertEquals(2, count($mockDispatcher->getMethodCallHistory("dispatch")));

        //The goodStr is more similar to the target than the badStr
        $this->assertTrue(
            $this->embeddingService->compareEmbedding($targetEmbed, $goodEmbed) >
            $this->embeddingService->compareEmbedding($targetEmbed, $badEmbed)
        );
    }

    public function testGetEmbeddingWorksWithTextWithBackslash(){
        $mockDispatcher = MockObjectProvider::instance()->getMockInstance(HttpRequestDispatcher::class);
        $mockEmbeddingService = new OpenAIEmbeddingService($mockDispatcher, $this->credentialsService);
        $target = "This is target\\\\";

        // Turns a list of numerical embedding vectors into a Response obj
        $toBody = function($embeds){
            $mappedEmbeds = array_map(fn($embed) => ["embedding" => $embed], $embeds);
            $out = [
                "data" => $mappedEmbeds
            ];

            return new \GuzzleHttp\Psr7\Response(body: json_encode($out));
        };

        $mockDispatcher->returnValue("dispatch", $toBody([0, 1]));
        $embeddings = $mockEmbeddingService->embedStrings([$target]);
        $hist = $mockDispatcher->getMethodCallHistory("dispatch");
        /** @var \Kinikit\Core\HTTP\Request\Request $req */
        $req = $hist[0][0];
        $payload = $req->getPayload();

        $this->assertTrue(str_contains($payload, "\"input\":[\"This is target\"]"));

    }

    public function testHitRealAPI(){
        $target = "This is target sentence which we should go for";
        $goodStr = "A sentence to aim at";
        $badStr = "A sentence to avoid looking at";
        try {
            $targetEmbed = $this->embeddingService->embedString($target);
            [$goodEmbed, $badEmbed] = $this->embeddingService->embedStrings([$goodStr, $badStr]);
//            print_r($badEmbed);
            $this->fail();
        } catch (Exception $e) {
            $this->assertTrue(str_contains($e->getMessage(), "Incorrect API key"));
            $this->assertTrue(str_contains($e->getMessage(), self::OPENAI_API_KEY));
        }
    }
}