<?php


namespace Kinintel\Services\ImportExport;


use Kiniauth\Objects\Account\Account;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\ImportExport\Export;
use Kinintel\ValueObjects\ImportExport\ImportAnalysis;
use Kinintel\ValueObjects\ImportExport\ImportItem;

class ImportService {

    private DatasourceService $datasourceService;
    private DatasetService $datasetService;
    private DashboardService $dashboardService;

    public function __construct(DatasourceService $datasourceService,
                                DatasetService $datasetService,
                                DashboardService $dashboardService) {
        $this->datasourceService = $datasourceService;
        $this->datasetService = $datasetService;
        $this->dashboardService = $dashboardService;
    }


    /**
     * Analyse an import and return an import analysis object, which is for frontend to display
     * so people can checkbox which parts of the file they want.
     *
     * @param Export $export
     * @param ?string $projectKey
     * @param integer $accountId
     *
     * @return ImportAnalysis
     */
    public function analyseImport(Export $export, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT): ImportAnalysis {

        /**
         * Loop through supplied datasource instances and check whether or not we need to include them
         */
        $datasourceInstanceItems = [];
        foreach ($export->getDatasourceInstances() as $datasourceInstance) {
            $item = new ImportItem($datasourceInstance->getTitle());
            try {
                $this->datasourceService->getDatasourceInstanceByTitle($datasourceInstance->getTitle(), $projectKey, $accountId);
                $item->setExists(true);
            } catch (ObjectNotFoundException $e) {
                $item->setExists(false);
            }
            $datasourceInstanceItems[] = $item;
        }

        /**
         * Loop through supplied dataset instances and check whether or not we need to include them
         */
        $datasetInstanceItems = [];
        foreach ($export->getDatasetInstances() as $datasetInstance) {
            $item = new ImportItem($datasetInstance->getTitle());
            try {
                $this->datasetService->getDataSetInstanceByTitle($datasetInstance->getTitle(), $projectKey, $accountId);
                $item->setExists(true);
            } catch (ObjectNotFoundException $e) {
                $item->setExists(false);
            }
            $datasetInstanceItems[] = $item;
        }


        $dashboardItems = [];
        foreach ($export->getDashboards() as $dashboard) {
            $item = new ImportItem($dashboard->getTitle());
            try {
                $this->dashboardService->getDashboardByTitle($dashboard->getTitle(), $projectKey, $accountId);
                $item->setExists(true);
            } catch (ObjectNotFoundException $e) {
                $item->setExists(false);
            }
            $dashboardItems[] = $item;
        }

        return new ImportAnalysis($datasourceInstanceItems, $datasetInstanceItems, $dashboardItems);


    }


    /**
     * Import an export into a project
     *
     * @param Export $export
     * @param string $projectKey
     * @param int $accountId
     */
    public function importToProject(Export $export, ImportConfig $importConfig, $projectKey, $accountId = null) {

    }

}