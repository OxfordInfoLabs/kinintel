<?php

namespace Kinintel\Test\Services\Hook;

use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\Services\Hook\DatasourceHookService;
use Kinintel\Test\ValueObjects\Hook\TestHookConfig;
use Kinintel\TestBase;

include_once "autoloader.php";

class DatasourceHookServiceTest extends TestBase {

    private $dataProcessorService;

    private $scheduledTaskService;

    private $hookService;

    private $activeRecordInterceptor;

    public function setUp(): void {
        $this->dataProcessorService = MockObjectProvider::mock(DataProcessorService::class);
        $this->scheduledTaskService = MockObjectProvider::mock(ScheduledTaskService::class);
        $this->activeRecordInterceptor = MockObjectProvider::mock(ActiveRecordInterceptor::class);
        $this->hookService = new DatasourceHookService($this->dataProcessorService, $this->scheduledTaskService,
            Container::instance()->get(ObjectBinder::class), $this->activeRecordInterceptor);
        AuthenticationHelper::login("admin@kinicart.com", "password");
    }

    public function testDataProcessorTriggeredCorrectlyForDataProcessorBasedHook() {

        $newHook = new DatasourceHookInstance("My Hook", "testhook", "testprocessor", null, "testprocessor", null, "add");
        $newHook->save();

        $this->hookService->processHooks("testhook", "add");

        $this->assertTrue($this->dataProcessorService->methodWasCalled("triggerDataProcessorInstance", [
            "testprocessor"
        ]));

        // Clean up
        $this->hookService->deleteHook($newHook->getId());

    }

    public function testScheduledTaskIsTriggeredCorrectlyForTaskBasedHook() {

        $newHook = new DatasourceHookInstance("My Hook", "testhook2", null, 25, null, 25, "add");
        $newHook->save();

        $this->hookService->processHooks("testhook2", "add");

        $this->assertTrue($this->scheduledTaskService->methodWasCalled("triggerScheduledTask", [
            25
        ]));

        // Clean up
        $this->hookService->deleteHook(2);
    }


    public function testHookIsInvokedCorrectlyForManualClassBasedHook() {

        // Programme test mock
        $mockHook = MockObjectProvider::mock(DatasourceHook::class);
        Container::instance()->addInterfaceImplementation(DatasourceHook::class, "test", get_class($mockHook));
        Container::instance()->set(get_class($mockHook), $mockHook);
        $mockHook->returnValue("getConfigClass", TestHookConfig::class);

        $newHook = new DatasourceHookInstance("My Hook", "testhook3", "test", [
            "testProp" => "Hello World"
        ], null, null, "add");
        $newHook->save();

        $data = [["name" => "Dave"], ["name" => "Mary"]];

        $this->hookService->processHooks("testhook3", "add", $data);

        // Check process hook called correctly
        $this->assertTrue($mockHook->methodWasCalled("processHook", [
            new TestHookConfig("Hello World"), "add", $data, null
        ]));

        // Clean up
        $this->hookService->deleteHook($newHook->getId());

    }

    public function testHookTriggeredCorrectlyForHookConfiguredForAllModes() {

        $newHook = new DatasourceHookInstance("My Hook", "testhook4", null, 25, null, 25, "all");
        $newHook->save();

        $this->hookService->processHooks("testhook4", "add");

        $this->assertTrue($this->scheduledTaskService->methodWasCalled("triggerScheduledTask", [
            25
        ]));

        // Clean up
        $this->hookService->deleteHook(4);

    }


    public function testHookNotTriggeredIfDisabled() {

        $newHook = new DatasourceHookInstance("My Hook", "testhook5", null, 25, null, 25, "all", false);
        $newHook->save();

        $this->hookService->processHooks("testhook5", "add");

        $this->assertFalse($this->scheduledTaskService->methodWasCalled("triggerScheduledTask", [
            25
        ]));

        // Clean up
        $this->hookService->deleteHook(5);

    }

    public function testHookExecutedWithSecurityBypassedIfExecuteInsecureFlagSet() {

        // Control case first
        $newHook1 = new DatasourceHookInstance("My Hook", "testhook6", null, null, null, 25, "all", true, false);
        $newHook1->save();

        $this->hookService->processHooks("testhook6", "add");

        // Check scheduled task called
        $this->assertTrue($this->scheduledTaskService->methodWasCalled("triggerScheduledTask", [
            25
        ]));

        $this->assertFalse($this->activeRecordInterceptor->methodWasCalled("executeInsecure"));


        // Insecure case
        $newHook2 = new DatasourceHookInstance("My Hook", "testhook6", null, null, null, 25, "all", true, true);
        $newHook2->save();

        $this->hookService->processHooks("testhook6", "add");

        $this->assertTrue($this->activeRecordInterceptor->methodWasCalled("executeInsecure"));

        // Clean up
        $this->hookService->deleteHook($newHook1->getId());
        $this->hookService->deleteHook($newHook2->getId());

    }

    public function testCanSaveAHookInstance() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSetInstance = new DatasourceHookInstance("Test Hook", "testSource");

        $id = $this->hookService->saveHookInstance($dataSetInstance, 5, 1);

        // Check saved correctly in db
        $dataset = DatasourceHookInstance::fetch($id);
        $this->assertEquals(1, $dataset->getAccountId());
        $this->assertEquals(5, $dataset->getProjectKey());


        $reSet = $this->hookService->getDatasourceHookById($id);
        $this->assertEquals("Test Hook", $reSet->getTitle());
        $this->assertEquals("testSource", $reSet->getDatasourceInstanceKey());

        // Remove the data set instance
        $this->hookService->deleteHook($id);

        try {
            $this->hookService->getDatasourceHookById($id);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }
    }


    public function testCanFilterHooksByAccountIdAndProjectKey() {

        Container::instance()->get(DatabaseConnection::class)->execute("DELETE FROM ki_datasource_hook_instance");

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $hook1 = new DatasourceHookInstance("Hook 1", "datasourceKey1", "hookKey", accountId: null, id: 101);
        $hook2 = new DatasourceHookInstance("Hook 2", "datasourceKey2", "hookKey", accountId: 1, projectKey: "myKey", id: 102);
        $hook3 = new DatasourceHookInstance("Hook 3", "datasourceKey2", "hookKey", accountId: 2, projectKey: "myKey", id: 103);
        $hook4 = new DatasourceHookInstance("Hook 4", "datasourceKey2", "hookKey", accountId: 2, projectKey: "myOtherKey", id: 104);
        $hook5 = new DatasourceHookInstance("Hook 5", "datasourceKey2", "hookKey", accountId: 3, id: 105);

        $hook1->save();
        $hook2->save();
        $hook3->save();
        $hook4->save();
        $hook5->save();

        $hook1 = DatasourceHookInstance::fetch(101);
        $hook2 = DatasourceHookInstance::fetch(102);
        $hook3 = DatasourceHookInstance::fetch(103);
        $hook4 = DatasourceHookInstance::fetch(104);

        $hooks = $this->hookService->filterDatasourceHookInstances(accountId: null);
        $this->assertEquals([$hook1], $hooks);

        $hooks = $this->hookService->filterDatasourceHookInstances(accountId: 1);
        $this->assertEquals([$hook2], $hooks);

        $hooks = $this->hookService->filterDatasourceHookInstances(accountId: 2);
        $this->assertEquals([$hook3, $hook4], $hooks);

        $hooks = $this->hookService->filterDatasourceHookInstances(projectKey: "myKey", accountId: 2);
        $this->assertEquals([$hook3], $hooks);

        $hooks = $this->hookService->filterDatasourceHookInstances(projectKey: "myKey", accountId: null);
        $this->assertEquals([], $hooks);

        $hooks = $this->hookService->filterDatasourceHookInstances(projectKey: "myKey", accountId: 3);
        $this->assertEquals([], $hooks);
    }

}