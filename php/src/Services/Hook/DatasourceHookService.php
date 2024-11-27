<?php

namespace Kinintel\Services\Hook;

use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;

class DatasourceHookService {

    public function __construct(
        private DataProcessorService $dataProcessorService,
        private ScheduledTaskService $scheduledTaskService
    ) {
    }

    /**
     * @param string $hookKey
     * @return DatasourceHookInstance
     */
    public function getDatasourceHookByKey($hookKey) {
        return DatasourceHookInstance::fetch($hookKey);
    }

    public function getDatasourceInstanceHooksForDatasource($datasourceInstanceKey) {
        return DatasourceHookInstance::filter("WHERE datasourceInstanceKey = ?", $datasourceInstanceKey);
    }

    public function getDatasourceHookInstancesForDatasourceInstanceAndMode($key, $mode) {
        return DatasourceHookInstance::filter("WHERE datasourceInstanceKey = ? AND hookMode = ?", $key, $mode);
    }

    /**
     * Creates a hook instance with reference to the data processor instance, source query
     */
    public function createHook($datasourceInstanceKey, $dataProcessorInstanceKey, $hookMode, $config) {
        $hookInstance = new DatasourceHookInstance($datasourceInstanceKey, $dataProcessorInstanceKey, $hookMode, $config);
        $hookInstance->save();
    }

    public function processHooks($datasourceKey, $updateMode) {

        /** @var DatasourceHookInstance[] $hooks */
        $hooks = $this->getDatasourceHookInstancesForDatasourceInstanceAndMode($datasourceKey, $updateMode);


        // Process all applicable hooks
        foreach ($hooks as $hook) {

            // Check for processor based hooks
            if ($processorKey = $hook->getDataProcessorInstanceKey()) {
                $this->dataProcessorService->triggerDataProcessorInstance($processorKey);
            }
            // Check for scheduled task based hooks
            else if ($scheduledTaskId = $hook->getScheduledTaskId()) {
                $this->scheduledTaskService->triggerScheduledTask($scheduledTaskId);
            }
        }

    }

    public function deleteHook($hookKey) {
        $hook = $this->getDatasourceHookByKey($hookKey);
        $hook->remove();
    }

}