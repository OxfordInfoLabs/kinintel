<?php


namespace Kinintel\Test\Services\Feed;

use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Services\Feed\FeedService;
use Kinintel\TestBase;

include_once "autoloader.php";

class FeedServiceTest extends TestBase {


    /**
     * @var FeedService
     */
    private $feedService;


    public function setUp(): void {
        $this->feedService = new FeedService();
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
        ], $feedId);
        $expected->setDatasetLabel(new DatasetInstanceSearchResult(2, "Test Dataset"));
        $this->assertEquals($expected, $reFeed);

        // Update and check
        $reFeed->setExporterConfiguration(["config" => "Goodbye"]);
        $this->feedService->saveFeed($reFeed, "wiperBlades", 2);

        $reReFeed = $this->feedService->getFeedById($feedId);
        $expected = new FeedSummary("/new/feed", 2, ["param1", "param2"], "test", [
            "config" => "Goodbye"
        ], $feedId);
        $expected->setDatasetLabel(new DatasetInstanceSearchResult(2, "Test Dataset"));
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

}