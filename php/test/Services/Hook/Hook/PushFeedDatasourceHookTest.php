<?php

namespace Kinintel\Test\Services\Hook\Hook;

use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kiniauth\ValueObjects\QueuedTask\QueueItem;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Services\Feed\FeedService;
use Kinintel\Services\Feed\PushFeedService;
use Kinintel\Services\Hook\Hook\PushFeedDatasourceHook;
use Kinintel\ValueObjects\Hook\Hook\PushFeedDatasourceHookConfig;
use Kinintel\ValueObjects\Hook\MetaData\SQLDatabaseDatasourceHookUpdateMetaData;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class PushFeedDatasourceHookTest extends TestCase {

    /**
     * @var PushFeedService|MockObject
     */
    private $pushFeedService;

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
        $this->pushFeedService = MockObjectProvider::mock(PushFeedService::class);
        $this->hook = new PushFeedDatasourceHook($this->pushFeedService);

    }


    public function testPushFeedNotQueuedIfNoDataPassedToHook() {


        $dbConnection = MockObjectProvider::mock(DatabaseConnection::class);
        $dbConnection->returnValue("getLastAutoIncrementId", 25);

        $config = new PushFeedDatasourceHookConfig(25);

        $this->hook->processHook($config, "add", [

        ], new SQLDatabaseDatasourceHookUpdateMetaData($dbConnection));


        $this->assertFalse($this->pushFeedService->methodWasCalled("queuePushFeed"));

    }


    public function testPushFeedQueuedIfDataPassedToHook() {

        $dbConnection = MockObjectProvider::mock(DatabaseConnection::class);
        $dbConnection->returnValue("getLastAutoIncrementId", 25);

        $config = new PushFeedDatasourceHookConfig(25);

        $this->hook->processHook($config, "add", [
            [
                "date" => "2025-06-01"
            ],
            [
                "date" => "2025-07-01"
            ]
        ], new SQLDatabaseDatasourceHookUpdateMetaData($dbConnection));


        $this->assertTrue($this->pushFeedService->methodWasCalled("queuePushFeed", [
            25
        ]));

    }


}