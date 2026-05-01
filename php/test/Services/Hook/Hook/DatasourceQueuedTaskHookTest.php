<?php

namespace Kinintel\Test\Services\Hook\Hook;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Hook\Hook\DatasourceQueuedTaskHook;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Hook\Hook\DatasourceQueuedTaskHookConfig;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatasourceQueuedTaskHookTest extends TestCase {


    /**
     * @var DatasourceService
     */
    private DatasourceService $datasourceService;

    /**
     * @var DatasourceQueuedTaskHook
     */
    private DatasourceQueuedTaskHook $hook;

    /**
     * @var QueuedTaskService
     */
    private QueuedTaskService $queuedTaskService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::mock(DatasourceService::class);
        $this->queuedTaskService = MockObjectProvider::mock(QueuedTaskService::class);
        $this->hook = new DatasourceQueuedTaskHook($this->datasourceService, $this->queuedTaskService);
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


    /**
     * @throws UnsupportedDatasetException
     */
    public function testQueuedTaskCreatedWithDataProcessedViaInterimDatasetIfFieldsSupplied() {

        $config = new DatasourceQueuedTaskHookConfig([
            new Field("name"),
            new Field("age"),
            new Field("shoeSize", null, "[[age | subtract 10]]")
        ]);

        $this->hook->processHook($config, DatasourceHookInstance::HOOK_MODE_ADD, [
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