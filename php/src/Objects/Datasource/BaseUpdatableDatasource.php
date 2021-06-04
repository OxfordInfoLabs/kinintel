<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;

abstract class BaseUpdatableDatasource extends BaseDatasource implements UpdatableDatasource {

    /**
     * @var DatasourceUpdateConfig
     */
    private $updateConfig;

    /**
     * BaseUpdatableDatasource constructor.
     * @param DatasourceUpdateConfig $updateConfig
     */
    public function __construct($config = null, $authenticationCredentials = null, $updateConfig = null, $validator = null) {
        parent::__construct($config, $authenticationCredentials, $validator);
        $this->updateConfig = $updateConfig;

    }

    /**
     * Default to returning the standard datasource update config
     *
     * @return string
     */
    public function getUpdateConfigClass() {
        return DatasourceUpdateConfig::class;
    }


    /**
     * @return DatasourceUpdateConfig
     */
    public function getUpdateConfig() {
        return $this->updateConfig;
    }

    /**
     * @param DatasourceUpdateConfig $updateConfig
     */
    public function setUpdateConfig($updateConfig) {
        $this->updateConfig = $updateConfig;
    }


}