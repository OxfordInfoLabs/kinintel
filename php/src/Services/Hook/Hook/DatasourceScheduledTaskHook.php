<?php

namespace Kinintel\Services\Hook\Hook;

use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\ValueObjects\Hook\Hook\DatasourceScheduledTaskHookConfig;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;

class DatasourceScheduledTaskHook implements DatasourceHook {

    /**
     * @param DatasourceService $datasourceService
     */
    public function __construct(
        private readonly DatasourceService $datasourceService,
        private readonly ScheduledTaskService $scheduledTaskService
    ) {
    }

    /**
     * Get the config class for this datasource hook.
     *
     * @return string
     */
    public function getConfigClass(): string {
        return DatasourceScheduledTaskHookConfig::class;
    }

    /**
     * Process the hook
     *
     * @param DatasourceScheduledTaskHookConfig $hookConfig
     * @param string $updateMode
     * @param mixed $updateData
     *
     * @return void
    */
    public function processHook($hookConfig, $updateMode, $updateData) {
        $data = null;
        if ($hookConfig->getFields()) {
            $data = (new ArrayTabularDataset($hookConfig->getFields(), $updateData))->getAllData();
        } else {
            $data = $updateData;
        }

        $newTaskSummary = new ScheduledTaskSummary(
            "test",
            "test scheduled task",
            [],
            []
        );

        $taskId = $this->scheduledTaskService->saveScheduledTask($newTaskSummary);

        $allTasks = $this->scheduledTaskService->listScheduledTasks();

        print_r($allTasks);

    }
}