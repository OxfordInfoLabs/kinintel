<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateResult;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;

interface UpdatableDatasource {

    const UPDATE_MODE_ADD = "add";
    const UPDATE_MODE_DELETE = "delete";
    const UPDATE_MODE_REPLACE = "replace";
    const UPDATE_MODE_UPDATE = "update";


    /**
     * Get the class in use for update config
     *
     * @return string
     */
    public function getUpdateConfigClass();


    /**
     * Get update config
     *
     * @return DatasourceUpdateConfig
     */
    public function getUpdateConfig();


    /**
     * Set update config
     *
     * @param DatasourceUpdateConfig $updateConfig
     */
    public function setUpdateConfig($updateConfig);


    /**
     * Update this datasource using a supplied dataset and the update mode supplied.
     *
     * @param Dataset $dataset
     *
     * @param string $updateMode
     * @return DatasourceUpdateResult
     */
    public function update($dataset, $updateMode = self::UPDATE_MODE_ADD);


    /**
     * Delete multiple items from a data source using a filter junction.
     *
     * @param FilterJunction $filterJunction
     * @return void
     */
    public function filteredDelete($filterJunction);


    /**
     * Event method called when a parent datasource instance is saved to provide
     * an opportunity to update e.g. structural stuff based on updated config.
     *
     */
    public function onInstanceSave();


    /**
     * Event method called when a parent datasource instance is deleted to provide
     * an opportunity to clean up - e.g. free resources etc.
     *
     * @return mixed
     */
    public function onInstanceDelete();


}
