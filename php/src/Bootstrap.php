<?php

namespace Kinintel;

use Kiniauth\Services\Attachment\AttachmentStorage;
use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\ApplicationBootstrap;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Services\Alert\AlertGroupTask;
use Kinintel\Services\DataProcessor\DataProcessorTask;
use Kinintel\Services\Util\AttachmentStorage\GoogleCloudAttachmentStorage;
use Kinintel\Services\Util\SQLiteFunctions\DotProduct;

class Bootstrap implements ApplicationBootstrap {


    public function setup() {
        SQLite3DatabaseConnection::addCustomFunction(new DotProduct());

        // Inject task implementations specific to kinintel
        Container::instance()->addInterfaceImplementation(Task::class, "alertgroup", AlertGroupTask::class);
        Container::instance()->addInterfaceImplementation(Task::class, "dataprocessor", DataProcessorTask::class);


        // Add attachment storage for google
        Container::instance()->addInterfaceImplementation(AttachmentStorage::class, "google-cloud", GoogleCloudAttachmentStorage::class);

    }
}