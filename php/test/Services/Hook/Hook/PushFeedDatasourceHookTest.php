<?php

namespace Kinintel\Test\Services\Hook\Hook;

use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Services\Hook\Hook\PushFeedDatasourceHook;
use Kinintel\ValueObjects\Hook\Hook\PushFeedDatasourceHookConfig;
use Kinintel\ValueObjects\Hook\MetaData\SQLDatabaseDatasourceHookUpdateMetaData;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class PushFeedDatasourceHookTest extends TestCase {

    /**
     * @var QueuedTaskService|MockObject
     */
    private $queuedTaskService;

    /**
     * @var PushFeedDatasourceHook
     */
    private $hook;

    /**
     * Set up
     *
     * @return void
     */
    public function setUp(): void {

        // Create a hook with a mock service.
        $this->queuedTaskService = MockObjectProvider::mock(QueuedTaskService::class);
        $this->hook = new PushFeedDatasourceHook($this->queuedTaskService);

    }


    public function testNoQueuedTaskCreatedIfNoDataPassedToHook() {


        $dbConnection = MockObjectProvider::mock(DatabaseConnection::class);
        $dbConnection->returnValue("getLastAutoIncrementId", 25);

        $config = new PushFeedDatasourceHookConfig(25, "https://pushtome.com", [
            "name" => "Roger",
            "age" => 23
        ], ["id"], "id", 33, [
            "x-additional" => "testing"
        ], "PUT");

        $this->hook->processHook($config, "add", [

        ], new SQLDatabaseDatasourceHookUpdateMetaData($dbConnection));


        $this->assertFalse($this->queuedTaskService->methodWasCalled("queueTask"));

    }


    public function testNoQueuedTaskCreatedIfNoAutoIncrementValueReturnedForAutoIncrementBasedFeed() {
        $dbConnection = MockObjectProvider::mock(DatabaseConnection::class);
        $dbConnection->returnValue("getLastAutoIncrementId", 0);

        $config = new PushFeedDatasourceHookConfig(25, "https://pushtome.com", [
            "name" => "Roger",
            "age" => 23
        ], ["id"], "id", 33, [
            "x-additional" => "testing"
        ], "PUT");

        $this->hook->processHook($config, "add", [
            [
                "test" => 1
            ]
        ], new SQLDatabaseDatasourceHookUpdateMetaData($dbConnection));


        $this->assertFalse($this->queuedTaskService->methodWasCalled("queueTask"));

    }

    public function testQueuedTaskCreatedWhenHookCalledForAutoIncrementBasedFeed() {

        $dbConnection = MockObjectProvider::mock(DatabaseConnection::class);
        $dbConnection->returnValue("getLastAutoIncrementId", 25);

        $config = new PushFeedDatasourceHookConfig(25, "https://pushtome.com", [
            "name" => "Roger",
            "age" => 23
        ], ["id"], "id", 33, [
            "x-additional" => "testing"
        ], "PUT");

        $this->hook->processHook($config, "add", [
            [
                "test" => 1
            ]
        ], new SQLDatabaseDatasourceHookUpdateMetaData($dbConnection));


        $this->assertTrue($this->queuedTaskService->methodWasCalled("queueTask", [
            "push-feed",
            "pushfeed",
            "Push Feed -> https://pushtome.com (" . md5("Roger:23") . ")",
            ["feedId" => 25, "parameterValues" => [
                "name" => "Roger",
                "age" => 23,
                "id" => 25
            ], "statefulParameters" => ["id"],
                "pushUrl" => "https://pushtome.com",
                "headers" => [
                    "x-additional" => "testing"
                ],
                "method" => "PUT",
                "signingKeyPairId" => 33
            ], null, 0
        ]));

    }


    public function testQueuedTaskCreatedWhenHookCalledContainingColumnNamesAsParameterValues() {

        $dbConnection = MockObjectProvider::mock(DatabaseConnection::class);
        $dbConnection->returnValue("getLastAutoIncrementId", 25);

        $config = new PushFeedDatasourceHookConfig(25, "https://pushtome.com", [
            "fromDate" => "[[date]]",
            "age" => 25
        ]);

        $this->hook->processHook($config, "add", [
            [
                "date" => "2025-06-01"
            ],
            [
                "date" => "2025-07-01"
            ]
        ], new SQLDatabaseDatasourceHookUpdateMetaData($dbConnection));


        $this->assertTrue($this->queuedTaskService->methodWasCalled("queueTask", [
            "push-feed",
            "pushfeed",
            "Push Feed -> https://pushtome.com (" . md5("[[date]]:25") . ")",
            ["feedId" => 25, "parameterValues" => [
                "fromDate" => "2025-06-01",
                "age" => 25
            ], "statefulParameters" => [], "pushUrl" => "https://pushtome.com",
                "headers" => [
                ],
                "method" => "POST"
            ], null, 0
        ]));

    }

    public function testTaskNotQueuedIfTwoTasksAlreadyExistForSameDescriptions() {

        $dbConnection = MockObjectProvider::mock(DatabaseConnection::class);
        $dbConnection->returnValue("getLastAutoIncrementId", 25);

        $config = new PushFeedDatasourceHookConfig(25, "https://pushtome.com", [
            "name" => "Roger",
            "age" => 23
        ], ["id"], "id", 33, [
            "x-additional" => "testing"
        ], "PUT");


        // Try with one matching item
        $this->queuedTaskService->returnValue("listQueuedTasks", [
            new QueueItem("push-feed", 1, "pushfeed",
                "Push Feed -> https://pushtome.com (" . md5("Roger:23") . ")", new \DateTime(), QueueItem::STATUS_PENDING)
        ],
            ["push-feed"]);

        $this->hook->processHook($config, "add", [
            [
                "test" => 1
            ]
        ], new SQLDatabaseDatasourceHookUpdateMetaData($dbConnection));


        $this->assertTrue($this->queuedTaskService->methodWasCalled("queueTask"));

        // Reset method calls
        $this->queuedTaskService->resetMethodCallHistory("queueTask");

        // Try with one matching item
        $this->queuedTaskService->returnValue("listQueuedTasks", [
            new QueueItem("push-feed", 1, "pushfeed",
                "Push Feed -> https://pushtome.com (" . md5("Roger:23") . ")", new \DateTime(), QueueItem::STATUS_PENDING),
            new QueueItem("push-feed", 1, "pushfeed",
                "Push Feed -> https://pushtome.com (" . md5("Roger:23") . ")", new \DateTime(), QueueItem::STATUS_PENDING)
        ],
            ["push-feed"]);

        $this->hook->processHook($config, "add", [
            [
                "test" => 1
            ]
        ], new SQLDatabaseDatasourceHookUpdateMetaData($dbConnection));


        $this->assertFalse($this->queuedTaskService->methodWasCalled("queueTask"));

    }

}