<?php

namespace Kinintel\Test\Services\DataProcessor\Command;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\Command\CommandDataProcessor;
use Kinintel\TestBase;
use Kinintel\ValueObjects\DataProcessor\Configuration\Command\CommandDataProcessorConfiguration;

include_once "autoloader.php";

class CommandDataProcessorTest extends TestBase {

    private CommandDataProcessor $processor;

    protected function setUp(): void {
        $this->processor = new CommandDataProcessor();
    }


    public function testCommandExecutedCorrectly() {

        $config = new CommandDataProcessorConfiguration("echo hi");

        $instance = new DataProcessorInstance("myKey", "Command Runner", "command", $config);

        $this->processor->process($instance);

        $this->assertTrue(true);

    }

    public function testBadCommandThrowsException() {

        $config = new CommandDataProcessorConfiguration("notacommand hi");

        $instance = new DataProcessorInstance("myKey", "Command Runner", "command", $config);

        try {
            $this->processor->process($instance);
            $this->fail("Should haven thrown");
        } catch (\Exception $e) {
            $this->assertEquals("Command exited with status code 127. Output:\n", $e->getMessage());
        }

    }

}