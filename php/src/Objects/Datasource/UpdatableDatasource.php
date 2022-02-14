<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;

interface UpdatableDatasource {

    const UPDATE_MODE_ADD = "add";
    const UPDATE_MODE_DELETE = "delete";
    const UPDATE_MODE_REPLACE = "replace";


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
     * @return mixed|void
     */
    public function update($dataset, $updateMode = self::UPDATE_MODE_ADD);




}