<?php

namespace Kinintel;

use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\ApplicationBootstrap;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Services\Alert\AlertGroupTask;
use Kinintel\Services\DataProcessor\DataProcessorTask;

class Bootstrap implements ApplicationBootstrap {

    public function setup() {

        // Inject task implementations specific to kinintel
        Container::instance()->addInterfaceImplementation(Task::class, "alertgroup", AlertGroupTask::class);
        Container::instance()->addInterfaceImplementation(Task::class, "dataprocessor", DataProcessorTask::class);
    }
}