<?php


namespace Kinintel\Test\Services\Feed;

use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Security\Captcha\GoogleRecaptchaProvider;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\MVC\ContentSource\StringContentSource;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\FeedNotFoundException;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Feed\Feed;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Feed\FeedService;
use Kinintel\Services\Util\FilterQueryParser;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Feed\FeedWebsiteConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;
use Kinintel\ValueObjects\Transformation\TransformationInstance;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

class FeedServiceTest extends TestBase {


    /**
     * @var FeedService
     */
    private $feedService;


    /**
     * @var MockObject
     */
    private $datasetService;


    /**
     * @var MockObject
     */
    private $securityService;

    /**
     * @var MockObject
     */
    private $captchaProvider;

    /**
     * @var FilterQueryParser
     */
    private $filterQueryParser;

    public function setUp(): void {

        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->securityService = MockObjectProvider::instance()->getMockInstance(SecurityService::class);
        $this->captchaProvider = MockObjectProvider::instance()->getMockInstance(GoogleRecaptchaProvider::class);
        $this->filterQueryParser = MockObjectProvider::mock(FilterQueryParser::class);

        $this->feedService = new FeedService($this->datasetService, $this->securityService, $this->captchaProvider, $this->filterQueryParser);
    }


    public function testCanCreateReadUpdateAndDeleteFeeds() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $feedSummary = new FeedSummary("/new/feed", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $feedId = $this->feedService->saveFeed($feedSummary, "soapSuds", 2);

        $this->assertNotNull($feedId);

        $reFeed = $this->feedService->getFeedById($feedId);
        $expected = new FeedSummary("/new/feed", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ], false, '', "query", 0, null, $feedId);
        $expected->setDatasetLabel(new DatasetInstanceSearchResult(2, "Test Dataset", null, null, [], null, "test-json"));
        $this->assertEquals($expected, $reFeed);

        // Update and check
        $reFeed->setExporterConfiguration(["config" => "Goodbye"]);
        $this->feedService->saveFeed($reFeed, "wiperBlades", 2);

        $reReFeed = $this->feedService->getFeedById($feedId);
        $expected = new FeedSummary("/new/feed", 2, ["param1", "param2"], "test", [
            "config" => "Goodbye"
        ], false, '', "query", 0, null, $feedId);
        $expected->setDatasetLabel(new DatasetInstanceSearchResult(2, "Test Dataset", null, null, [], null, "test-json"));
        $this->assertEquals($expected, $reReFeed);


        // Delete feed
        $this->feedService->removeFeed($feedId);

        try {
            $this->feedService->getFeedById($feedId);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }


    }

    public function testCanGetFeedByPath() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $feedSummary = new FeedSummary("/pathed/feed", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $feedId = $this->feedService->saveFeed($feedSummary, "dnsAbuse", 1);

        $byPath = $this->feedService->getFeedByPath("/pathed/feed");
        $this->assertEquals($feedId, $byPath->getId());

        $this->feedService->removeFeed($feedId);
    }


    public function testCanCheckIfFeedUrlAvailableAndValidatedOnSave() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $feedSummary = new FeedSummary("/test/feed", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $feedId = $this->feedService->saveFeed($feedSummary, "soapSuds", 2);

        // New one should be ok
        $this->assertTrue($this->feedService->isFeedURLAvailable("/test/newfeed", null, 2));

        // Check same url in same account not ok
        $this->assertFalse($this->feedService->isFeedURLAvailable("/test/feed", null, 2));
        $this->assertFalse($this->feedService->isFeedURLAvailable("/test/feed", 300, 2));

        // Same url different account ok.
        $this->assertTrue($this->feedService->isFeedURLAvailable("/test/feed", null, 1));

        // Same url, same item ok
        $this->assertTrue($this->feedService->isFeedURLAvailable("/test/newfeed", $feedId, 2));


        $feedSummary = new FeedSummary("/test/feed", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);
        try {
            $this->feedService->saveFeed($feedSummary, "soapSuds", 2);
            $this->fail("Should have failed");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["path"]["duplicatePath"]));
        }

    }

    public function testCanGetFilteredFeeds() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $feedSummary = new FeedSummary("/filter/feed3", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $feed3Id = $this->feedService->saveFeed($feedSummary, "wiperBlades", 2);


        $feedSummary = new FeedSummary("/filter/feed2", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $feed2Id = $this->feedService->saveFeed($feedSummary, null, 2);


        $feedSummary = new FeedSummary("/filter/feed1", 3, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $feed1Id = $this->feedService->saveFeed($feedSummary, null, 1);


        // Check a path based filter
        $filteredFeeds = $this->feedService->filterFeeds("/filter", null, 0, 10, null);
        $this->assertEquals(3, sizeof($filteredFeeds));
        $this->assertEquals(Feed::fetch($feed1Id)->returnSummary(), $filteredFeeds[0]);
        $this->assertEquals(Feed::fetch($feed2Id)->returnSummary(), $filteredFeeds[1]);
        $this->assertEquals(Feed::fetch($feed3Id)->returnSummary(), $filteredFeeds[2]);

        // Dataset title based filter
        $filteredFeeds = $this->feedService->filterFeeds("Account", null, 0, 10, null);
        $this->assertEquals(1, sizeof($filteredFeeds));
        $this->assertEquals(Feed::fetch($feed1Id)->returnSummary(), $filteredFeeds[0]);

        // Account filter
        $filteredFeeds = $this->feedService->filterFeeds("", null, 0, 10, 1);
        $this->assertEquals(1, sizeof($filteredFeeds));
        $this->assertEquals(Feed::fetch($feed1Id)->returnSummary(), $filteredFeeds[0]);

        // Project filter
        $filteredFeeds = $this->feedService->filterFeeds("", "wiperBlades", 0, 10, 2);
        $this->assertEquals(1, sizeof($filteredFeeds));
        $this->assertEquals(Feed::fetch($feed3Id)->returnSummary(), $filteredFeeds[0]);

        // Offset and limits
        $filteredFeeds = $this->feedService->filterFeeds("/filter", null, 1, 10, null);
        $this->assertEquals(2, sizeof($filteredFeeds));
        $this->assertEquals(Feed::fetch($feed2Id)->returnSummary(), $filteredFeeds[0]);
        $this->assertEquals(Feed::fetch($feed3Id)->returnSummary(), $filteredFeeds[1]);

        $filteredFeeds = $this->feedService->filterFeeds("/filter", null, 0, 2, null);
        $this->assertEquals(2, sizeof($filteredFeeds));
        $this->assertEquals(Feed::fetch($feed1Id)->returnSummary(), $filteredFeeds[0]);
        $this->assertEquals(Feed::fetch($feed2Id)->returnSummary(), $filteredFeeds[1]);

    }


    public function testFeedNotFoundExceptionThrownIfNoFeedFoundMatchingUrl() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        try {
            $this->feedService->evaluateFeedByPath("bad/feed");
            $this->fail("Should have thrown here");
        } catch (FeedNotFoundException $e) {
            $this->assertEquals(new FeedNotFoundException("bad/feed"), $e);
        }

    }


    public function testCanEvaluateFeedWithBlankParametersAndTheseAreSetToBlankStrings() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $feedSummary = new FeedSummary("filter/feed3", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $this->feedService->saveFeed($feedSummary, "wiperBlades", 2);

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            ["param1" => "",
                "param2" => ""],
            [],
            0,
            50,
            false,
            0
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);


        $response = $this->feedService->evaluateFeedByPath("filter/feed3");

        $this->assertEquals($expectedResponse, $response);

    }


    public function testCanEvaluateFeedWithParametersAndTheseAreMatchedCorrectlyToExposedParameters() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $feedSummary = new FeedSummary("filter/feed4", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $this->feedService->saveFeed($feedSummary, "wiperBlades", 2);

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            ["param1" => "Bingo",
                "param2" => "Bongo"],
            [],
            0,
            50,
            false,
            0
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);


        $response = $this->feedService->evaluateFeedByPath("filter/feed4", [
            "param1" => "Bingo",
            "param2" => "Bongo",
            "param3" => "Bango",
            "param4" => "Pinky"
        ]);

        $this->assertEquals($expectedResponse, $response);


    }


    public function testIfParametersSuppliedInCSVFormatTheyAreExplodedToArrays() {


        AuthenticationHelper::login("admin@kinicart.com", "password");


        $feedSummary = new FeedSummary("filter/feed5", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $this->feedService->saveFeed($feedSummary, "wiperBlades", 2);

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            ["param1" => ["Bingo", "Bongo", "Bango"],
                "param2" => ["Yes, this is me", "No, this is you"]
            ],
            [],
            0,
            50,
            false,
            0
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);


        // Check both normal CSV format and with enclosures
        $response = $this->feedService->evaluateFeedByPath("filter/feed5", [
            "param1" => "Bingo,Bongo,Bango",
            "param2" => '"Yes, this is me","No, this is you"'
        ]);


        $this->assertEquals($expectedResponse, $response);


    }

    public function testCacheTimePassedThroughToExportServiceIfSuppliedAsFeedConfig() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);


        $feedSummary = new FeedSummary("filter/feed6", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $feedId = $this->feedService->saveFeed($feedSummary, "wiperBlades", 2);


        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));
        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            ["param1" => "",
                "param2" => ""],
            [],
            0,
            50,
            false,
            0
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);

        $response = $this->feedService->evaluateFeedByPath("filter/feed6");
        $this->assertEquals($expectedResponse, $response);

        $feedSummary = $this->feedService->getFeedById($feedId);
        $feedSummary->setCacheTimeSeconds(120);
        $this->feedService->saveFeed($feedSummary, "wiperBlades", 2);


        $expectedResponse = new SimpleResponse(new StringContentSource("BANGO"));
        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            ["param1" => "",
                "param2" => ""],
            [],
            0,
            50,
            false,
            120
        ]);

        $response = $this->feedService->evaluateFeedByPath("filter/feed6");
        $this->assertEquals($expectedResponse, $response);

    }


    public function testAccessToFeedCheckedAgainstProjectPrivileges() {


        AuthenticationHelper::login("admin@kinicart.com", "password");


        $feedSummary = new FeedSummary("/new/feed", 2, ["param1", "param2"], "test", [
            "config" => "Hello"
        ]);

        $this->feedService->saveFeed($feedSummary, "soapSuds", 2);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", false, [
            Role::SCOPE_PROJECT, "feedaccess", "soapSuds"
        ]);

        try {
            $this->feedService->evaluateFeedByPath("/new/feed");
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true, [
            Role::SCOPE_PROJECT, "feedaccess", "soapSuds"
        ]);

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            ["param1" => "",
                "param2" => ""],
            [],
            0,
            50,
            false,
            0
        ]);

        $response = $this->feedService->evaluateFeedByPath("/new/feed");
        $this->assertEquals($expectedResponse, $response);

    }


    public function testRecaptchaProviderCalledIfConfiguredAsWebsiteConfig() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $feedSummary = new FeedSummary("filter/feed7", 2, [], "test", [
            "config" => "Hello"
        ], false, '', "query", 0, new FeedWebsiteConfig([], true, "SECRETKEY", 0.6));

        $this->feedService->saveFeed($feedSummary, "wiperBlades", 2);

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            [],
            [],
            0,
            50,
            false,
            0
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);


        // Try one without a valid request

        try {
            $this->feedService->evaluateFeedByPath("filter/feed7");
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }


        // Try one with an invalid request
        try {
            $this->feedService->evaluateFeedByPath("filter/feed7", [], 0, 50, new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }

        $_SERVER["HTTP_X_CAPTCHA_TOKEN"] = "BADCAPTCHA";
        $request = new Request(new Headers());
        $this->captchaProvider->returnValue("verifyCaptcha", false, [
            "BADCAPTCHA", $request
        ]);

        // Try one with an invalid request
        try {
            $this->feedService->evaluateFeedByPath("filter/feed7", [], 0, 50, $request);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }


        $_SERVER["HTTP_X_CAPTCHA_TOKEN"] = "CAPTCHAKEY";
        $request = new Request(new Headers());

        // Try one with a valid request
        $this->captchaProvider->returnValue("verifyCaptcha", true, [
            "CAPTCHAKEY", $request
        ]);

        $response = $this->feedService->evaluateFeedByPath("filter/feed7", [], 0, 50, $request);
        $this->assertEquals($expectedResponse, $response);

        // Confirm that the backend google service was configured correctly.
        $this->assertTrue($this->captchaProvider->methodWasCalled("setRecaptchaSecretKey", ["SECRETKEY"]));
        $this->assertTrue($this->captchaProvider->methodWasCalled("setRecaptchaScoreThreshold", [0.6]));


    }


    public function testRequestHostnameCheckedAgainstReferringDomainsIfSupplied() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $feedSummary = new FeedSummary("filter/feed8", 2, [], "test", [
            "config" => "Hello"
        ], false, '', "query", 0, new FeedWebsiteConfig(["happy.com", "test.sad.com"]));

        $this->feedService->saveFeed($feedSummary, "wiperBlades", 2);

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            [],
            [],
            0,
            50,
            false,
            0
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);

        // Try one without a valid request
        try {
            $this->feedService->evaluateFeedByPath("filter/feed8");
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }

        // Try one with an invalid request
        try {
            $this->feedService->evaluateFeedByPath("filter/feed8", [], 0, 50, new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }


        // Try ones with invalid referrers
        $_SERVER["HTTP_REFERER"] = "https://www.google.com/helloworld?myname=test";
        try {
            $this->feedService->evaluateFeedByPath("filter/feed8", [], 0, 50, new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }

        // Subdomain should not be good.
        $_SERVER["HTTP_REFERER"] = "https://www.happy.com/helloworld?myname=test";
        try {
            $this->feedService->evaluateFeedByPath("filter/feed8", [], 0, 50, new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }

        // Subdomain should not be good.
        $_SERVER["HTTP_REFERER"] = "https://sad.com/helloworld?myname=test";
        try {
            $this->feedService->evaluateFeedByPath("filter/feed8", [], 0, 50, new Request(new Headers()));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }


        // Valid referrers
        $_SERVER["HTTP_REFERER"] = "https://happy.com/mypath?hello=true";
        $response = $this->feedService->evaluateFeedByPath("filter/feed8", [], 0, 50, new Request(new Headers()));
        $this->assertEquals($expectedResponse, $response);

        // Valid referrers
        $_SERVER["HTTP_REFERER"] = "https://test.sad.com/mypath?hello=true";
        $response = $this->feedService->evaluateFeedByPath("filter/feed8", [], 0, 50, new Request(new Headers()));
        $this->assertEquals($expectedResponse, $response);


    }


    public function testIfAdvancedQueryingIsDisabledFilterTransformationNotAddedToFeed() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $feedSummary = new FeedSummary("/new/advancedquerydis", 2, [], "test", [
            "config" => "Hello"
        ]);

        $this->feedService->saveFeed($feedSummary, "soapSuds", 2);


        $expectedJunction = new FilterJunction([new Filter("[[id]]", 33, FilterType::eq)]);

        $this->filterQueryParser->returnValue("convertQueryToFilterJunction", $expectedJunction, [
            "id == 33"
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            [],
            [],
            0,
            50,
            false,
            0
        ]);

        $response = $this->feedService->evaluateFeed("/new/advancedquerydis", ["query" => "id == 33"]);
        $this->assertEquals($expectedResponse, $response);


    }


    public function testIfAdhocFilteringEnabledFilterTransformationIsAddedToFeedWhereAdhocParametersExist() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $feedSummary = new FeedSummary("/new/adhoc", 2, [], "test", [
            "config" => "Hello"
        ], true);

        $this->feedService->saveFeed($feedSummary, "soapSuds", 2);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);

        // Map expected transformation
        $expectedFilters = [
            new Filter("[[param1]]", 25, FilterType::eq),
            new Filter("[[param2]]", "*string*", FilterType::like),
            new Filter("[[param3]]", "smith", FilterType::similarto),
            new Filter("[[param4]]", ["mark", "john", "james"], FilterType::in)
        ];


        $expectedTransformationInstance = new TransformationInstance("filter", new FilterTransformation($expectedFilters));

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            [],
            [$expectedTransformationInstance],
            0,
            50,
            false,
            0
        ]);

        $response = $this->feedService->evaluateFeed("/new/adhoc",
            ["param1_eq" => 25, "param2_like" => "*string*", "param3_similarto" => "smith", "param4_in" => "mark,john,james"]
        );




        $this->assertEquals($expectedResponse, $response);

    }


    public function testIfAdvancedQueryingEnabledFilterTransformationIsAddedToTheFeed() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $feedSummary = new FeedSummary("/new/advancedquery", 2, [], "test", [
            "config" => "Hello"
        ], false, true, "mango");

        $this->feedService->saveFeed($feedSummary, "soapSuds", 2);


        $expectedJunction = new FilterJunction([new Filter("[[id]]", 33, FilterType::eq)]);

        $this->filterQueryParser->returnValue("convertQueryToFilterJunction", $expectedJunction, [
            "id == 33"
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", true);

        // Map expected transformation
        $expectedTransformationInstance = new TransformationInstance("filter", new FilterTransformation($expectedJunction->getFilters()));

        $expectedResponse = new SimpleResponse(new StringContentSource("BONZO"));

        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);

        $this->datasetService->returnValue("exportDatasetInstance", $expectedResponse, [
            $datasetInstance,
            "test",
            ["config" => "Hello"],
            [],
            [$expectedTransformationInstance],
            0,
            50,
            false,
            0
        ]);

        $response = $this->feedService->evaluateFeed("/new/advancedquery", ["mango" => "id == 33"]);
        $this->assertEquals($expectedResponse, $response);

    }

}