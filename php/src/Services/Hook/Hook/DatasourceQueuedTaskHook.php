<?php

namespace Kinintel\Services\Hook\Hook;

use Exception;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\ValueObjects\Hook\Hook\DatasourceQueuedTaskHookConfig;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;

class DatasourceQueuedTaskHook implements DatasourceHook {

    /**
     * @param QueuedTaskService $queuedTaskService
     */
    public function __construct(
        private $queuedTaskService,
    ) {
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
            "PushAPIQueue",
            "PushAPITask",
            "Push API signals",
            [
                "source" => $updateData[0]["source"] ?? null,
            ],
        );
    }
}