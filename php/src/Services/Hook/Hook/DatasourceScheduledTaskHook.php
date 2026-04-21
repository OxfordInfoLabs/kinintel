<?php

namespace Kinintel\Services\Hook\Hook;

use Kinintel\Exception\UnsupportedDatasetException;
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
     * @throws UnsupportedDatasetException
     */
    public function processHook($hookConfig, $updateMode, $updateData): void {

        //retrieve the data from the hook
        $data = null;
        if ($hookConfig->getFields()) {
            $data = (new ArrayTabularDataset($hookConfig->getFields(), $updateData))->getAllData();
        } else {
            $data = $updateData;
        }

        //filter data used supplied filters
        $filters = $hookConfig->getFilters();

        if ($filters) {
            $data = array_values(array_filter($data, function ($row) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (!isset($row[$key]) || $row[$key] !== $value) {
                        return false;
                    }
                }
                return true;
            }));
        }

        print_r($data);

        $newTaskSummary = new ScheduledTaskSummary(
            "pushAPI",
            "new signals for push API",
            [
                "source" => $data[0]["source"] ?? null,
            ],
            []
        );

        $taskId = $this->scheduledTaskService->saveScheduledTask($newTaskSummary);

        $allTasks = $this->scheduledTaskService->listScheduledTasks();

        //print_r($allTasks);

    }
}