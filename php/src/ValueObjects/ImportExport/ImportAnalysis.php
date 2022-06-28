<?php


namespace Kinintel\ValueObjects\ImportExport;


class ImportAnalysis {

    /**
     * @var ImportItem[]
     */
    private $datasourceInstanceItems;

    /**
     * @var ImportItem[]
     */
    private $datasetInstanceItems;


    /**
     * @var ImportItem[]
     */
    private $dashboardItems;

    /**
     * ImportAnalysis constructor.
     *
     * @param ImportItem[] $datasourceInstanceItems
     * @param ImportItem[] $datasetInstanceItems
     * @param ImportItem[] $dashboardItems
     */
    public function __construct($datasourceInstanceItems = [], $datasetInstanceItems = [], $dashboardItems = []) {
        $this->datasourceInstanceItems = $datasourceInstanceItems;
        $this->datasetInstanceItems = $datasetInstanceItems;
        $this->dashboardItems = $dashboardItems;
    }


    /**
     * @return ImportItem[]
     */
    public function getDatasourceInstanceItems() {
        return $this->datasourceInstanceItems;
    }

    /**
     * @param ImportItem[] $datasourceInstanceItems
     */
    public function setDatasourceInstanceItems($datasourceInstanceItems) {
        $this->datasourceInstanceItems = $datasourceInstanceItems;
    }

    /**
     * @return ImportItem[]
     */
    public function getDatasetInstanceItems() {
        return $this->datasetInstanceItems;
    }

    /**
     * @param ImportItem[] $datasetInstanceItems
     */
    public function setDatasetInstanceItems($datasetInstanceItems) {
        $this->datasetInstanceItems = $datasetInstanceItems;
    }

    /**
     * @return ImportItem[]
     */
    public function getDashboardItems() {
        return $this->dashboardItems;
    }

    /**
     * @param ImportItem[] $dashboardItems
     */
    public function setDashboardItems($dashboardItems) {
        $this->dashboardItems = $dashboardItems;
    }


}