<?php

namespace Kinintel\Services\Hook;

use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;

class DatasourceHookService {

    public function __construct(
        private DataProcessorService $dataProcessorService
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
        return DatasourceHookInstance::filter("datasourceInstanceKey = ?", [$datasourceInstanceKey]);
    }

    public function getDatasourceHookInstancesForDatasourceInstanceAndMode($key, $mode) {
        return DatasourceHookInstance::filter("datasourceInstanceKey = ? AND hookMode = ?'", [$key, $mode]);
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

        foreach ($hooks as $hook) {
            $processorKey = $hook->getDataProcessorInstanceKey();
            $this->dataProcessorService->triggerDataProcessorInstance($processorKey);
        }

    }

    public function deleteHook($hookKey) {
        $hook = $this->getDatasourceHookByKey($hookKey);
        $hook->remove();
    }

}