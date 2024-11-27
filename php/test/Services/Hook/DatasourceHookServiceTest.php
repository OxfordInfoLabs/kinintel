<?php

namespace Kinintel\Test\Services\Hook;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Hook\DatasourceHookService;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatasourceHookServiceTest extends TestCase {

    private $dataProcessorService;

    private $scheduledTaskService;

    private $hookService;

    public function setUp(): void {
        $this->dataProcessorService = MockObjectProvider::mock(DataProcessorService::class);
        $this->scheduledTaskService = MockObjectProvider::mock(ScheduledTaskService::class);
        $this->hookService = new DatasourceHookService($this->dataProcessorService, $this->scheduledTaskService);
    }

    public function testDataProcessorTriggeredCorrectlyForDataProcessorBasedHook() {

        $newHook = new DatasourceHookInstance("testhook", "testprocessor", null, "add");
        $newHook->save();

        $this->hookService->processHooks("testhook", "add");

        $this->assertTrue($this->dataProcessorService->methodWasCalled("triggerDataProcessorInstance", [
            "testprocessor"
        ]));

    }

    public function testScheduledTaskIsTriggeredCorrectlyForTaskBasedHook() {

        $newHook = new DatasourceHookInstance("testhook2", null, 25, "add");
        $newHook->save();

        $this->hookService->processHooks("testhook2", "add");

        $this->assertTrue($this->scheduledTaskService->methodWasCalled("triggerScheduledTask", [
            25
        ]));

    }
}