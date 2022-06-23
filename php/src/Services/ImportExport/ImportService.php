<?php


namespace Kinintel\Services\ImportExport;


use Kiniauth\Objects\Account\Account;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\ImportExport\Export;
use Kinintel\ValueObjects\ImportExport\ImportAnalysis;

class ImportService {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * @var DashboardService
     */
    private $dashboardService;


    /**
     * ImportService constructor.
     *
     * @param DatasourceService $datasourceService
     * @param DatasetService $datasetService
     * @param DashboardService $dashboardService
     */
    public function __construct($datasourceService, $datasetService, $dashboardService) {
        $this->datasourceService = $datasourceService;
        $this->datasetService = $datasetService;
        $this->dashboardService = $dashboardService;
    }


    /**
     * Analyse an import and return an import analysis object
     *
     * @param $export
     * @param null $projectKey
     * @param string $accountId
     *
     * @return ImportAnalysis
     */
    public function analyseImport($export, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

    }


    /**
     * Import an export into a project
     *
     * @param Export $export
     * @param string $projectKey
     * @param integer $accountId
     */
    public function importToProject($export, $projectKey, $accountId = null) {

    }

}