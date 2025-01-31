<?php

namespace Kinintel\Services\Hook\Hook;

use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Hook\Hook\DatasourceUpdateDatasourceHookConfig;

class DatasourceUpdateDatasourceHook implements DatasourceHook {


    /**
     * @param DatasourceService $datasourceService
     */
    public function __construct(private DatasourceService $datasourceService) {
    }


    /**
     * Get the config class for this datasource hook.
     *
     * @return string
     */
    public function getConfigClass() {
        return DatasourceUpdateDatasourceHookConfig::class;
    }

    /**
     * Process the hook
     *
     * @param DatasourceUpdateDatasourceHookConfig $hookConfig
     * @param string $updateMode
     * @param array $updateData
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

        // Update the target datasource
        $this->datasourceService->updateDatasourceInstanceByKey($hookConfig->getTargetDatasourceKey(),
            new DatasourceUpdate($updateMode == UpdatableDatasource::UPDATE_MODE_ADD ? $data : [],
                $updateMode == UpdatableDatasource::UPDATE_MODE_UPDATE ? $data : [],
                $updateMode == UpdatableDatasource::UPDATE_MODE_DELETE ? $data : [],
                $updateMode == UpdatableDatasource::UPDATE_MODE_REPLACE ? $data : []), true);

    }
}