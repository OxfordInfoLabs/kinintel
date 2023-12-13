<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding;

use Exception;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\MathsUtils;
use Kinikit\MVC\Response\Response;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Authentication\Generic\SingleKeyAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\HTTPHeaderAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;


class OpenAIEmbeddingService implements TextEmbeddingService {

    const SLEEP_SECONDS_WHEN_RATE_LIMITED = 5;

    public function __construct(
        private HTTPRequestDispatcher $dispatcher,
        private AuthenticationCredentialsService $credentialsService,
    ) {
    }

    /**
     * Embeds a string to a 1536 dimensional vector using the OpenAI API
     * @param string $text
     * @return float[]
     * @throws Exception
     */
    public function embedString(string $text): array {
        return $this->embedStrings([$text])[0];
    }

    public function compareEmbedding($embedding1, $embedding2) : float{
        return MathsUtils::dot($embedding1, $embedding2);
    }

    /**
     * Return an array of embedding objects
     * @param string[] $texts
     * @return array[]
     * @throws Exception
     */
    public function embedStrings(array $texts, $noAttempts = 0): array {
        $texts = array_map($this->sanitiseText(...), $texts);
        $payload = ['input'=>$texts, 'model'=>'text-embedding-ada-002'];
        $headers = $this->constructHeaders();
        $jsonPayload = json_encode($payload, JSON_INVALID_UTF8_IGNORE);
        if (!$jsonPayload) throw new Exception("Bad arguments passed to embed strings: " . print_r($texts, true));

        $request = new Request(
            "https://api.openai.com/v1/embeddings", "POST",
            payload: $jsonPayload, headers: $headers);

        $response = $this->dispatcher->dispatch($request);
        $jsonResponse = json_decode($response->getBody(), true);

        //Respond to rate limits
        $statusCode = $response->getStatusCode();
        if ($statusCode == Response::RESPONSE_RATE_LIMITED ||
            $statusCode == Response::RESPONSE_SERVICE_UNAVAILABLE) {

            if ($statusCode == Response::RESPONSE_RATE_LIMITED){
                Logger::log("OpenAI api was rate limited");
            }

            // Retry for a maximum of 2 minutes
            if ($noAttempts > 120 / self::SLEEP_SECONDS_WHEN_RATE_LIMITED){
                throw new Exception("OpenAI has been continually rate limited for too long");
            }
            sleep(self::SLEEP_SECONDS_WHEN_RATE_LIMITED);
            return $this->embedStrings($texts, $noAttempts + 1);
        }

        if ($response->getStatusCode() == Response::RESPONSE_SERVICE_UNAVAILABLE){
            sleep(self::SLEEP_SECONDS_WHEN_RATE_LIMITED);
            return $this->embedStrings($texts, $noAttempts + 1);
        }

        //Handle errors
        if (!$jsonResponse) throw new \Exception("Failed to bind bad OpenAI API response");
        if (isset($jsonResponse["error"])) throw new Exception($jsonResponse["error"]["message"]);

        $data = $jsonResponse["data"];
        if ($data && in_array("index", $data[0])){
            usort($data, fn($e1, $e2) => $e1["index"] <=> $e2["index"]);
        }

        $embeddings = array_map(fn($obj) => $obj["embedding"], $data);

        return $embeddings;

    }

    private function constructHeaders() {

        //Read the config
        $credentialsKey = Configuration::readParameter("openai.api.credentials.key");

        /** @var HTTPHeaderAuthenticationCredentials $credentials */
        $credentials = $this->credentialsService->getCredentialsInstanceByKey($credentialsKey)?->returnCredentials();
        if (!$credentials){
            throw new Exception("No api key found for openai in the config.");
        }
        $headers = $credentials->getAuthParams();
        $headers["Content-Type"] = "application/json";
        return new Headers($headers);
    }

    private function sanitiseText(string $text) {
        $out = str_replace("\\", "", $text);
        return $out;
    }
}