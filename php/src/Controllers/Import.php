<?php


namespace Kinintel\Controllers;

use Kinintel\Services\Datasource\DatasourceService;

class Import {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * Import constructor.
     *
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;
    }




}