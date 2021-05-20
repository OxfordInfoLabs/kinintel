<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;

/**
 * Default datasource which is used as fallback if a transformation cannot be fulfilled by
 * a given datasource (provided this datasource can handle it).
 *
 * Class DefaultDatasource
 * @package Kinintel\Objects\Datasource
 */
class DefaultDatasource extends SQLDatabaseDatasource {

    /**
     * @var Datasource
     */
    private $sourceDatasource;

    public function __construct($sourceDatasource) {
        $this->sourceDatasource = $sourceDatasource;
    }

}