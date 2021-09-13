<?php

namespace Kinintel;

use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\ApplicationBootstrap;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Services\Alert\AlertGroupTask;

class Bootstrap implements ApplicationBootstrap {

    public function setup() {
        Container::instance()->addInterfaceImplementation(Task::class, "alertgroup", AlertGroupTask::class);
    }
}