<?php

namespace Kinintel;

use Kiniauth\Services\Attachment\AttachmentStorage;
use Kiniauth\Services\Security\RouteInterceptor\APIRouteInterceptor;
use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\ApplicationBootstrap;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\Routing\RouteInterceptorProcessor;
use Kinintel\Services\Alert\AlertGroupTask;
use Kinintel\Services\DataProcessor\DataProcessorTask;
use Kinintel\Services\Util\AttachmentStorage\GoogleCloudAttachmentStorage;

class Bootstrap implements ApplicationBootstrap {

    /**
     * @var RouteInterceptorProcessor
     */
    private $routeInterceptorProcessor;

    /**
     * Bootstrap constructor.
     * @param RouteInterceptorProcessor $routeInterceptorProcessor
     */
    public function __construct($routeInterceptorProcessor) {
        $this->routeInterceptorProcessor = $routeInterceptorProcessor;
    }

    public function setup() {

        // Inject task implementations specific to kinintel
        Container::instance()->addInterfaceImplementation(Task::class, "alertgroup", AlertGroupTask::class);
        Container::instance()->addInterfaceImplementation(Task::class, "dataprocessor", DataProcessorTask::class);

        // Add route interceptor for feeds and import to match the API one
        $this->routeInterceptorProcessor->addInterceptor("feed/*", APIRouteInterceptor::class);
        $this->routeInterceptorProcessor->addInterceptor("import/*", APIRouteInterceptor::class);

        // Add attachment storage for google
        Container::instance()->addInterfaceImplementation(AttachmentStorage::class, "google-cloud", GoogleCloudAttachmentStorage::class);

    }
}