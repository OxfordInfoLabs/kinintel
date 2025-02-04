<?php

namespace Kinintel\Services\Hook;


use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;

class DatasourceHookService {

    public function __construct(
        private DataProcessorService    $dataProcessorService,
        private ScheduledTaskService    $scheduledTaskService,
        private ObjectBinder            $objectBinder,
        private ActiveRecordInterceptor $activeRecordInterceptor
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
        return DatasourceHookInstance::filter("WHERE datasourceInstanceKey = ? AND (hookMode = ? OR hookMode = ?) AND enabled", $key, $mode, DatasourceHookInstance::HOOK_MODE_ALL);
    }


    /**
     * @param $datasourceKey
     * @param $updateMode
     * @param $data
     * @return void
     * @throws \Kinikit\Core\Exception\AccessDeniedException
     * @throws \Throwable
     */
    public function processHooks($datasourceKey, $updateMode, $data = []) {

        /** @var DatasourceHookInstance[] $hooks */
        $hooks = $this->getDatasourceHookInstancesForDatasourceInstanceAndMode($datasourceKey, $updateMode);


        // Process all applicable hooks
        foreach ($hooks as $hookInstance) {

            // Check for processor based hooks
            if ($processorKey = $hookInstance->getDataProcessorInstanceKey()) {
                $this->dataProcessorService->triggerDataProcessorInstance($processorKey);
            } // Check for scheduled task based hooks
            else if ($scheduledTaskId = $hookInstance->getScheduledTaskId()) {
                $this->scheduledTaskService->triggerScheduledTask($scheduledTaskId);
            } else if ($hookKey = $hookInstance->getHookKey()) {

                $hook = Container::instance()->getInterfaceImplementation(DatasourceHook::class, $hookKey);
                $configClass = $hook->getConfigClass();
                $hookConfig = is_a($hookInstance->getHookConfig(), $configClass) ?:
                    $this->objectBinder->bindFromArray($hookInstance->getHookConfig(), $configClass);

                // Process the hook
                $hook->processHook($hookConfig, $updateMode, $data);

            }
        }

    }

    public function deleteHook($hookKey) {
        $hook = $this->getDatasourceHookByKey($hookKey);
        $hook->remove();
    }

}