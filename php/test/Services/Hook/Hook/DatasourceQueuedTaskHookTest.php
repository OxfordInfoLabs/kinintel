<?php

namespace Kinintel\Test\Services\Hook\Hook;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Services\Hook\Hook\DatasourceQueuedTaskHook;
use Kinintel\ValueObjects\Hook\Hook\DatasourceQueuedTaskHookConfig;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatasourceQueuedTaskHookTest extends TestCase {

    /**
     * @var DatasourceQueuedTaskHook
     */
    private DatasourceQueuedTaskHook $hook;

    /**
     * @var QueuedTaskService
     */
    private QueuedTaskService $queuedTaskService;


    public function setUp(): void {
        $this->queuedTaskService = MockObjectProvider::mock(QueuedTaskService::class);
        $this->hook = new DatasourceQueuedTaskHook($this->queuedTaskService);
    }

    /**
     * @throws UnsupportedDatasetException
     */
    public function testQueuedTaskCreatedWithPassedDataWhenNoFieldsPassed() {

        $config = new DatasourceQueuedTaskHookConfig();

        $this->hook->processHook($config, null, [
            [
                "name" => "Mark",
                "age" => 22
            ],
            [
                "name" => "John",
                "age" => 33
            ]
        ]);

        $this->assertTrue($this->queuedTaskService->methodWasCalled(
            "queueTask",
            [
                "PushAPIQueue",
                "PushAPITask",
                "Push API signals",
                [
                    "source" => null
                ]
            ])
        );

    }

}