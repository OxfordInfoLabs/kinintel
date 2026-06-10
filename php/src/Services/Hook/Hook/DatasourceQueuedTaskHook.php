<?php

namespace Kinintel\Services\Hook\Hook;

use Exception;
use Kinikit\Core\Configuration\Configuration;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\ValueObjects\Hook\Hook\DatasourceQueuedTaskHookConfig;
use Kiniauth\Services\Workflow\Task\Queued\Processor\GoogleCloudQueuedTaskProcessor;

class DatasourceQueuedTaskHook implements DatasourceHook {

    /**
     * @param GoogleCloudQueuedTaskProcessor $queuedTaskService
     * @param string $queueName
     */
    public function __construct(
        private GoogleCloudQueuedTaskProcessor $queuedTaskService,
        private string $queueName
    ) {
        $this->queueName = Configuration::readParameter("pushapi.queue");
    }

    /**
     * Get the config class for this datasource hook.
     *
     * @return string
     */
    public function getConfigClass(): string {
        return DatasourceQueuedTaskHookConfig::class;
    }

    /**
     * Process the hook
     *
     * @param DatasourceQueuedTaskHookConfig $hookConfig
     * @param string $updateMode
     * @param mixed $updateData
     *
     * @return void
     * @throws UnsupportedDatasetException
     * @throws Exception
     */
    public function processHook($hookConfig, $updateMode, $updateData): void {

        $this->queuedTaskService->queueTask(
            $this->queueName,
            "PushAPITask",
            "Push API signals",
            [
                "source" => $updateData[0]["source"] ?? null,
            ],
        );
    }
}