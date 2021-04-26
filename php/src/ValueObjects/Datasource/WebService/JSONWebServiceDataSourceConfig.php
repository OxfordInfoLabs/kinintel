<?php


namespace Kinintel\ValueObjects\Datasource\WebService;


/**
 * Extended config for JSON web service mappings
 *
 * Class JSONWebServiceDataSourceConfig
 * @package Kinintel\ValueObjects\Datasource\WebService
 */
class JSONWebServiceDataSourceConfig extends WebserviceDataSourceConfig {

    /**
     * @var JSONWebServiceResultMapping
     */
    private $resultMapping;

    /**
     * @return JSONWebServiceResultMapping
     */
    public function getResultMapping() {
        return $this->resultMapping ?? new JSONWebServiceResultMapping();
    }

    /**
     * @param JSONWebServiceResultMapping $resultMapping
     */
    public function setResultMapping($resultMapping) {
        $this->resultMapping = $resultMapping;
    }


}