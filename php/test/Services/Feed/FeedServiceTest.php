<?php


namespace Kinintel\Test\Services\Feed;

use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\MVC\ContentSource\StringContentSource;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Controllers\Account\Dataset;
use Kinintel\Exception\FeedNotFoundException;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Feed\Feed;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Feed\FeedService;
use Kinintel\TestBase;
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

    public function setUp(): void {

        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->feedService = new FeedService($this->datasetService);
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
        ], 0, $feedId);
        $expected->setDatasetLabel(new DatasetInstanceSearchResult(2, "Test Dataset", null, null, null, null, "test-json"));
        $this->assertEquals($expected, $reFeed);

        // Update and check
        $reFeed->setExporterConfiguration(["config" => "Goodbye"]);
        $this->feedService->saveFeed($reFeed, "wiperBlades", 2);

        $reReFeed = $this->feedService->getFeedById($feedId);
        $expected = new FeedSummary("/new/feed", 2, ["param1", "param2"], "test", [
            "config" => "Goodbye"
        ], 0, $feedId);
        $expected->setDatasetLabel(new DatasetInstanceSearchResult(2, "Test Dataset", null, null, null, null, "test-json"));
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
            $this->feedService->evaluateFeed("bad/feed");
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

        $response = $this->feedService->evaluateFeed("filter/feed3");
        $this->assertEquals($expectedResponse, $response);

    }

    public function testCacheTimePassedThroughToExportServiceIfSuppliedAsFeedConfig() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $datasetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $this->datasetService->returnValue("getDataSetInstance", $datasetInstance, [2]);


        $feedSummary = new FeedSummary("filter/feed4", 2, ["param1", "param2"], "test", [
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

        $response = $this->feedService->evaluateFeed("filter/feed4");
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

        $response = $this->feedService->evaluateFeed("filter/feed4");
        $this->assertEquals($expectedResponse, $response);

    }


}