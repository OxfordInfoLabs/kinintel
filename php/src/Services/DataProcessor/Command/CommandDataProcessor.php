<?php

namespace Kinintel\Services\DataProcessor\Command;

use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\ValueObjects\DataProcessor\Configuration\Command\CommandDataProcessorConfiguration;

class CommandDataProcessor implements DataProcessor {

    public function getConfigClass() {
        return CommandDataProcessorConfiguration::class;
    }

    public function process($instance) {

        /** @var CommandDataProcessorConfiguration $config */
        $config = $instance->returnConfig();

        passthru($config->getCommand(), $resultCode);

        if ($resultCode !== 0) {
            throw new \Exception("Command exited with status code $resultCode");
        }

    }

    public function onInstanceSave($instance) {
        // TODO: Implement onInstanceSave() method.
    }

    public function onInstanceDelete($instance) {
        // TODO: Implement onInstanceDelete() method.
    }

    public function onRelatedObjectSave($instance, $relatedObject) {
        // TODO: Implement onRelatedObjectSave() method.
    }
}