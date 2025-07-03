<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\Command;

class CommandDataProcessorConfiguration {

    private string $command;

    /**
     * @param string $command
     */
    public function __construct(string $command) {
        $this->command = $command;
    }

    public function getCommand(): string {
        return $this->command;
    }

    public function setCommand(string $command): void {
        $this->command = $command;
    }

}