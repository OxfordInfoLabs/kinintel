<?php


namespace Kinintel\Objects\Datasource\WebService;


use Kinintel\ValueObjects\Dataset\Dataset;

/**
 * Testing implementation of the web service data source
 *
 * Class TestWebServiceDataSource
 * @package Kinintel\Objects\Datasource\WebService
 */
class TestWebServiceDataSource extends WebServiceDatasource {

    /**
     * Take the raw result from a web service and convert back to data set.
     *
     * @param $result
     * @return Dataset
     */
    public function materialiseWebServiceResult($result) {
        return $result;
    }
}