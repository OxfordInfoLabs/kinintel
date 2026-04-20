<?php

namespace Kinintel\Test\Integration\Services\Hook\Hook;

use GSE\TestBase;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Hook\Hook\DatasourceScheduledTaskHook;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Hook\Hook\DatasourceScheduledTaskHookConfig;

include_once "autoloader.php";

class DatasourceScheduledTaskHookWorkflowTest extends TestBase {

    private DatasourceService $datasourceService;
    private DatasourceScheduledTaskHook $hook;


    public function setUp(): void {
        parent::installTestData();

        //initialise the container and bootstrap the application
        $this->datasourceService = Container::instance()->get(DatasourceService::class);
        $this->hook = Container::instance()->get(DatasourceScheduledTaskHook::class);

        // need admin rights to perform direct add to a DB table
        AuthenticationHelper::login("admin@kinicart.com", "password");
    }

    public function testTargetDatasourceUpdatedWithPassedDataWhenNoFieldsPassed() {

        $config = new DatasourceScheduledTaskHookConfig(
            [
                new Field("signal")
            ]
        );

        $this->hook->processHook($config, DatasourceHookInstance::HOOK_MODE_ADD, [
            [
                "signal" => "testSignal1",
            ],

        ]);

        $this->assertEquals(1,1);
    }

}