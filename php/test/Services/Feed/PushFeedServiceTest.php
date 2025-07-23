<?php

namespace Kinintel\Test\Services\Feed;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\Security\KeyPairService;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kiniauth\ValueObjects\Security\KeyPairSigningOutputFormat;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\Persistence\Database\Vendors\SQLite3\CustomFunctions\DateFormat;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Objects\Feed\PushFeed;
use Kinintel\Objects\Feed\PushFeedSummary;
use Kinintel\Services\Dataset\Exporter\JSONContentSource;
use Kinintel\Services\Feed\FeedService;
use Kinintel\Services\Feed\PushFeedService;
use Kinintel\TestBase;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

class PushFeedServiceTest extends TestBase {


    /**
     * @var FeedService|MockObject
     */
    private $feedService;

    /**
     * @var MockObject
     */
    private $queuedTaskService;

    /**
     * @var HttpRequestDispatcher|MockObject
     */
    private $requestDispatcher;

    /**
     * @var NotificationService|MockObject
     */
    private $notificationService;

    /**
     * @var KeyPairService|MockObject
     */
    private $keyPairService;

    /**
     * @var PushFeedService
     */
    private $pushFeedService;

    public function setUp(): void {

        $this->feedService = MockObjectProvider::mock(FeedService::class);
        $this->queuedTaskService = MockObjectProvider::instance()->getMockInstance(QueuedTaskService::class);
        $this->requestDispatcher = MockObjectProvider::mock(HttpRequestDispatcher::class);
        $this->notificationService = MockObjectProvider::mock(NotificationService::class);
        $this->keyPairService = MockObjectProvider::mock(KeyPairService::class);

        $this->pushFeedService = new PushFeedService($this->feedService, $this->queuedTaskService, $this->requestDispatcher, $this->notificationService, $this->keyPairService);
    }


    public function testCanCreateReadFilterAndRemovePushFeeds() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $pushFeed1 = new PushFeedSummary("Example Push 1", "/test", "https://bodgemeout.com", "id", "id", "",
            ["param1" => "bing", "param2" => "bong"], 22, ["content-type" => "application/json"], \Kinikit\Core\HTTP\Request\Request::METHOD_PUT,
            "myDatasource");

        $id1 = $this->pushFeedService->savePushFeed($pushFeed1, "bongo", 1);
        $this->assertNotNull($id1);

        $pushFeed2 = new PushFeedSummary("Example Push 2", "/source", "https://bodgemeout.com", "id", "id", "",
            ["param1" => "bing", "param2" => "bong"], 22, ["content-type" => "application/json"], \Kinikit\Core\HTTP\Request\Request::METHOD_PUT,
            "otherDatasource");

        $id2 = $this->pushFeedService->savePushFeed($pushFeed2, null, 1);
        $this->assertNotNull($id2);

        $pushFeed3 = new PushFeedSummary("Example Push 3", "/home", "https://bodgemeout.com", "id", "id", "",
            ["param1" => "bing", "param2" => "bong"], 22, ["content-type" => "application/json"], \Kinikit\Core\HTTP\Request\Request::METHOD_PUT,
            "myDatasource");

        $id3 = $this->pushFeedService->savePushFeed($pushFeed3, null, 2);
        $this->assertNotNull($id3);


        // Check some filtered results
        $this->assertEquals([PushFeedSummary::fetch(1)], $this->pushFeedService->filterPushFeeds("", "bongo", 0, 10, 1));
        $this->assertEquals([PushFeedSummary::fetch(1), PushFeedSummary::fetch(2)], $this->pushFeedService->filterPushFeeds("", null, 0, 10, 1));
        $this->assertEquals([PushFeedSummary::fetch(3)], $this->pushFeedService->filterPushFeeds("", null, 0, 10, 2));


        $this->pushFeedService->removePushFeed(1);

        try {
            PushFeed::fetch(1);
            $this->fail("Should have deleted");
        } catch (ObjectNotFoundException $e) {
        }


    }


    public function testQueuedTaskCreatedForPushFeedIfNoneExistsForSameTitle() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $pushFeed = new PushFeed(new PushFeedSummary("Home", "/testme", "https://phonehome.com", "id", "id", "", [
            "param1" => "Hello",
            "param2" => 33
        ]));

        $id = $this->pushFeedService->savePushFeed($pushFeed, null, 2);

        // Ensure no overlapping tasks
        $this->queuedTaskService->returnValue("listQueuedTasks", [], ["push-feed"]);


        // Queue a push feed.
        $this->pushFeedService->queuePushFeed($id);

        $this->assertTrue($this->queuedTaskService->methodWasCalled("queueTask", [
            "push-feed",
            "pushfeed",
            "Push Feed -> https://phonehome.com (" . md5("Hello:33") . ")",
            ["pushFeedId" => $id],
            null,
            0
        ]));

    }


    public function testQueuedTaskNotCreatedForPushFeedIfOneAlreadyExistsForSameTitle() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $pushFeed = new PushFeed(new PushFeedSummary("Home", "/testme", "https://phonehome.com", "id", "id", "", [
            "param1" => "Hello",
            "param2" => 33
        ]));

        $id = $this->pushFeedService->savePushFeed($pushFeed, null, 2);

        // Ensure no overlapping tasks
        $this->queuedTaskService->returnValue("listQueuedTasks", [
            new QueueItem("push-feed", "test", "pushfeed",
                "Push Feed -> https://phonehome.com (" . md5("Hello:33") . ")", new \DateTime(), QueueItem::STATUS_PENDING)
        ], ["push-feed"]);


        // Queue a push feed.
        $this->pushFeedService->queuePushFeed($id);

        $this->assertFalse($this->queuedTaskService->methodWasCalled("queueTask", [
            "push-feed",
            "pushfeed",
            "Push Feed -> https://phonehome.com (" . md5("Hello:33") . ")",
            ["pushFeedId" => $id],
            null,
            0
        ]));

    }

    public function testPushFeedProcessedCorrectlyForSimplePushFeedWithValidEndpoint() {
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $pushFeed = new PushFeed(new PushFeedSummary("Home", "/testmepush", "https://phonehome.com", "id", "id", 99, [
            "param1" => "Hello",
            "param2" => 33
        ]));

        $id = $this->pushFeedService->savePushFeed($pushFeed, null, 2);


        $data = [["id" => 99, "name" => "Bob"],
            ["id" => 100, "name" => "Mary"], ["id" => 101, "name" => "Jane"]];

        $expectedResponse = new SimpleResponse(new JSONContentSource($data));

        $this->feedService->returnValue("evaluateFeedByPath", $expectedResponse, [
            "/testmepush",
            ["param1" => "Hello",
                "param2" => 33, "id" => 99], 0, 1000
        ]);

        $expectedRequest = new \Kinikit\Core\HTTP\Request\Request("https://phonehome.com", Request::METHOD_POST, [],
            json_encode([
                ["id" => 100, "name" => "Mary"], ["id" => 101, "name" => "Jane"]], JSON_INVALID_UTF8_IGNORE),
            new \Kinikit\Core\HTTP\Request\Headers([\Kinikit\Core\HTTP\Request\Headers::CONTENT_TYPE => "application/json",
                Headers::DATE => date(\DateTimeInterface::RFC7231)]));

        // Check external URL was called
        $this->requestDispatcher->returnValue("dispatch", new Response(new ReadOnlyStringStream("OK"), 200, new \Kinikit\Core\HTTP\Response\Headers([]), $expectedRequest), [
            $expectedRequest
        ]);

        // Process push feed
        $this->pushFeedService->processPushFeed($id);


        // Check last pushed sequence value updated
        $pushFeed = PushFeed::fetch($id);
        $this->assertEquals(101, $pushFeed->getLastPushedSequenceValue());
    }


    public function testPushFeedProcessedCorrectlyForAdvancedPushFeedWithValidEndpointAndKeyPairSigning() {
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $pushFeed = new PushFeed(new PushFeedSummary("Home", "/testmepush", "https://phonehome.com", "id", "id", 99, [
            "param1" => "Hello",
            "param2" => 33
        ], 12, ["X-ADDITIONAL-HEADER" => "YES"], \Kinikit\Core\HTTP\Request\Request::METHOD_PUT));

        $id = $this->pushFeedService->savePushFeed($pushFeed, null, 2);


        $data = [["id" => 99, "name" => "Bob"],
            ["id" => 100, "name" => "Mary"], ["id" => 101, "name" => "Jane"]];

        $expectedResponse = new SimpleResponse(new JSONContentSource($data));

        $this->feedService->returnValue("evaluateFeedByPath", $expectedResponse, [
            "/testmepush",
            ["param1" => "Hello",
                "param2" => 33, "id" => 99], 0, 1000
        ]);


        $jsonData = json_encode([
            ["id" => 100, "name" => "Mary"], ["id" => 101, "name" => "Jane"]], JSON_INVALID_UTF8_IGNORE);

        $date = date(\DateTimeInterface::RFC7231);

        $signatureFields = "PUT\napplication/json\n$date\n$jsonData";

        $this->keyPairService->returnValue("signData", "SIGNEDCOPY", [
            $signatureFields,
            12,
            KeyPairSigningOutputFormat::Base64
        ]);

        $signature = 'sig=SIGNEDCOPY';
        $signatureInput = 'sig=("method" "content-type" "date" "body");created=' . date("U") . ';alg="rsa-sha512"';

        $expectedRequest = new \Kinikit\Core\HTTP\Request\Request("https://phonehome.com", Request::METHOD_PUT, [],
            $jsonData,
            new \Kinikit\Core\HTTP\Request\Headers([\Kinikit\Core\HTTP\Request\Headers::CONTENT_TYPE => "application/json",
                Headers::DATE => date(\DateTimeInterface::RFC7231),
                "X-ADDITIONAL-HEADER" => "YES",
                "Signature-Input" => $signatureInput,
                "Signature" => $signature
                ]));

        // Check external URL was called
        $this->requestDispatcher->returnValue("dispatch", new Response(new ReadOnlyStringStream("OK"), 200, new \Kinikit\Core\HTTP\Response\Headers([]), $expectedRequest), [
            $expectedRequest
        ]);

        // Process push feed
        $this->pushFeedService->processPushFeed($id);


        // Check last pushed sequence value updated
        $pushFeed = PushFeed::fetch($id);
        $this->assertEquals(101, $pushFeed->getLastPushedSequenceValue());
    }


    public function testNotificationCreatedToDesignatedGroupsWhenErrorsOccurredInPushFeedIfNotificationGroupsSupplied() {


        AuthenticationHelper::login("admin@kinicart.com", "password");


        $notificationGroup = new NotificationGroup(new NotificationGroupSummary("Test"), null, 2);
        $notificationGroup->save();


        $pushFeed = new PushFeed(new PushFeedSummary("Home", "/testmepushbad", "https://phonehome.com", "id", "id", 99, [
            "param1" => "Hello",
            "param2" => 33
        ], triggerDatasourceKey: "", failedPushNotificationTitle: "Push Feed Failed", failedPushNotificationDescription: "An attempt to push data to [[pushUrl]] has been unsuccessful.  Please see below for error log.\n\n[[errorLog]]",
            notificationGroups: [new NotificationGroupSummary("test", id: $notificationGroup->getId())]));

        $id = $this->pushFeedService->savePushFeed($pushFeed, null, 2);


        $data = [["id" => 99, "name" => "Bob"],
            ["id" => 100, "name" => "Mary"], ["id" => 101, "name" => "Jane"]];


        $expectedResponse = new SimpleResponse(new JSONContentSource($data));

        $this->feedService->returnValue("evaluateFeedByPath", $expectedResponse, [
            "/testmepushbad",
            ["param1" => "Hello",
                "param2" => 33, "id" => 99], 0, 1000
        ]);


        $expectedRequest = new \Kinikit\Core\HTTP\Request\Request("https://phonehome.com", Request::METHOD_POST, [],
            json_encode([
                ["id" => 100, "name" => "Mary"], ["id" => 101, "name" => "Jane"]], JSON_INVALID_UTF8_IGNORE),
            new \Kinikit\Core\HTTP\Request\Headers([\Kinikit\Core\HTTP\Request\Headers::CONTENT_TYPE => "application/json",
                Headers::DATE => date(\DateTimeInterface::RFC7231)]));

        $this->requestDispatcher->returnValue("dispatch", new Response(new ReadOnlyStringStream("Bad Response"), 500,
            new \Kinikit\Core\HTTP\Response\Headers([]), $expectedRequest), [
            $expectedRequest
        ]);

        // Process push feed
        $this->pushFeedService->processPushFeed($id);


        $this->assertTrue($this->notificationService->methodWasCalled("createNotification", [
            new NotificationSummary("Push Feed Failed", "An attempt to push data to https://phonehome.com has been unsuccessful.  Please see below for error log.\n\nBad Response", null, [new NotificationGroupSummary("Test", id: 1)]),
            null,
            2
        ]));

        // Check last pushed sequence value updated
        $pushFeed = PushFeed::fetch($id);
        $this->assertNull($pushFeed->getLastPushedSequenceValue());


    }

}