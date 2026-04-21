<?php

namespace Kinintel\Test\Integration\Services\Hook\Hook;

use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\Hook\Hook\DatasourceScheduledTaskHook;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Hook\Hook\DatasourceScheduledTaskHookConfig;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatasourceScheduledTaskHookWorkflowTest extends TestCase {

    private DatasourceScheduledTaskHook $hook;


    public function setUp(): void {

        //initialise the container and bootstrap the application
        $this->hook = Container::instance()->get(DatasourceScheduledTaskHook::class);


    }

    /**
     * @throws UnsupportedDatasetException
     */
    public function testWebhookWorksWithPassedDataWhenNoFieldsPassed() {

        $config = new DatasourceScheduledTaskHookConfig(
            [],
            [
                "abuse_type" => "testType",
            ]
        );

        $this->hook->processHook($config, DatasourceHookInstance::HOOK_MODE_ADD, [
            [
                "signal" => "testSignal1",
                "source" => "testSource1",
                "abuse_type" => "testType"
            ],
            [
                "signal" => "testSignal2",
                "source" => "testSource1",
                "abuse_type" => "testType2"
            ]

        ]);

        $this->assertEquals(1,1);
    }

}