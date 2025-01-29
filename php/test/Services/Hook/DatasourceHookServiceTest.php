<?php

namespace Kinintel\Test\Services\Hook;

use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Binding\ObjectBindingException;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\Services\Hook\DatasourceHookService;
use Kinintel\Test\ValueObjects\Hook\TestHookConfig;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatasourceHookServiceTest extends TestCase {

    private $dataProcessorService;

    private $scheduledTaskService;

    private $hookService;

    public function setUp(): void {
        $this->dataProcessorService = MockObjectProvider::mock(DataProcessorService::class);
        $this->scheduledTaskService = MockObjectProvider::mock(ScheduledTaskService::class);
        $this->hookService = new DatasourceHookService($this->dataProcessorService, $this->scheduledTaskService,
            Container::instance()->get(ObjectBinder::class));
    }

    public function testDataProcessorTriggeredCorrectlyForDataProcessorBasedHook() {

        $newHook = new DatasourceHookInstance("testhook", "testprocessor", null, "testprocessor", null, "add");
        $newHook->save();

        $this->hookService->processHooks("testhook", "add");

        $this->assertTrue($this->dataProcessorService->methodWasCalled("triggerDataProcessorInstance", [
            "testprocessor"
        ]));

    }

    public function testScheduledTaskIsTriggeredCorrectlyForTaskBasedHook() {

        $newHook = new DatasourceHookInstance("testhook2", null, 25, null, 25, "add");
        $newHook->save();

        $this->hookService->processHooks("testhook2", "add");

        $this->assertTrue($this->scheduledTaskService->methodWasCalled("triggerScheduledTask", [
            25
        ]));

    }


    public function testHookIsInvokedCorrectlyForManualClassBasedHook() {

        // Programme test mock
        $mockHook = MockObjectProvider::mock(DatasourceHook::class);
        Container::instance()->addInterfaceImplementation(DatasourceHook::class, "test", get_class($mockHook));
        Container::instance()->set(get_class($mockHook), $mockHook);
        $mockHook->returnValue("getConfigClass", TestHookConfig::class);

        $newHook = new DatasourceHookInstance("testhook3", "test", [
            "testProp" => "Hello World"
        ], null, null, "add");
        $newHook->save();

        $data = [["name" => "Dave"], ["name" => "Mary"]];

        $this->hookService->processHooks("testhook3", "add", $data);

        // Check process hook called correctly
        $this->assertTrue($mockHook->methodWasCalled("processHook", [
            new TestHookConfig("Hello World"), "add", $data
        ]));

    }

    public function testHookTriggeredCorrectlyForHookConfiguredForAllModes() {

        $newHook = new DatasourceHookInstance("testhook4", null, 25, null, 25, "all");
        $newHook->save();

        $this->hookService->processHooks("testhook4", "add");

        $this->assertTrue($this->scheduledTaskService->methodWasCalled("triggerScheduledTask", [
            25
        ]));

    }



}