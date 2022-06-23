<?php


namespace Kinintel\ValueObjects\ImportExport;


use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Dashboard\DashboardSearchResult;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\Objects\Datasource\DatasourceInstanceSummary;

class ExportableResources {

    /**
     * Array of matching datasource instance titles indexed by instance key.
     *
     *
     * @var string[string]
     */
    private $datasourceInstances;


    /**
     * Datasource dataset dependencies keyed in by parent key -> child id
     *
     * @var string[integer]
     */
    private $datasourceDatasetDependencies = [];

    /**
     * Array of matching dataset instance titles indexed by instance id
     *
     * @var string[integer]
     */
    private $datasetInstances;

    /**
     * Array of dataset dependencies keyed in by parent id -> child id
     *
     * @var integer[integer]
     */
    private $datasetDependencies = [];


    /**
     * Array of matching dashboard titles indexed by dashboard id.
     *
     * @var string[integer]
     */
    private $dashboards;


    /**
     * Array of dashboard dependencies keyed in by parent id -> child id
     *
     * @var integer[integer]
     */
    private $dashboardDependencies = [];


    /**
     * ExportableResources constructor.  Takes search result arrays and extracts the titles and dependencies etc
     *
     * @param DatasourceInstanceSearchResult[] $datasourceInstances
     * @param DatasetInstanceSearchResult[] $datasetInstances
     * @param DashboardSummary[] $dashboards
     */
    public function __construct($datasourceInstances = [], $datasetInstances = [], $dashboards = []) {


        // Get arrays of the identifiers for each type
        $datasourceInstanceKeys = ObjectArrayUtils::getMemberValueArrayForObjects("key", $datasourceInstances);
        $datasetInstanceIds = ObjectArrayUtils::getMemberValueArrayForObjects("id", $datasetInstances);
        $dashboardIds = ObjectArrayUtils::getMemberValueArrayForObjects("id", $dashboards);


        // Create datasource titles indexed by key
        $this->datasourceInstances = [];
        foreach ($datasourceInstances as $datasourceInstance) {
            $this->datasourceInstances[$datasourceInstance->getKey()] = $datasourceInstance->getTitle();
        }

        // Create dataset titles indexed by id
        $this->datasetInstances = [];
        foreach ($datasetInstances as $datasetInstance) {
            $this->datasetInstances[$datasetInstance->getId()] = $datasetInstance->getTitle();

            // Record any internal datasource dataset dependencies
            if ($datasetInstance->getDatasourceInstanceKey() && in_array($datasetInstance->getDatasourceInstanceKey(), $datasourceInstanceKeys)) {
                $this->datasourceDatasetDependencies[$datasetInstance->getDatasourceInstanceKey()] = $datasetInstance->getId();
            }
            // Record any internal dataset dependencies
            if ($datasetInstance->getDatasetInstanceId() && in_array($datasetInstance->getDatasetInstanceId(), $datasetInstanceIds)) {
                $this->datasetDependencies[$datasetInstance->getDatasetInstanceId()] = $datasetInstance->getId();
            }
        }

        // Create dashboard titles indexed by id
        $this->dashboards = [];
        foreach ($dashboards as $dashboard) {
            $this->dashboards[$dashboard->getId()] = $dashboard->getTitle();

            // Record any internal dashboard dependencies
            if ($dashboard->getParentDashboardId() && in_array($dashboard->getParentDashboardId(), $dashboardIds)) {
                $this->dashboardDependencies[$dashboard->getParentDashboardId()] = $dashboard->getId();
            }
        }

    }


    /**
     * @return string
     */
    public function getDatasourceInstances() {
        return $this->datasourceInstances;
    }

    /**
     * @param string $datasourceInstances
     */
    public function setDatasourceInstances($datasourceInstances) {
        $this->datasourceInstances = $datasourceInstances;
    }

    /**
     * @return string
     */
    public function getDatasetInstances() {
        return $this->datasetInstances;
    }

    /**
     * @param string $datasetInstances
     */
    public function setDatasetInstances($datasetInstances) {
        $this->datasetInstances = $datasetInstances;
    }

    /**
     * @return int
     */
    public function getDatasetDependencies() {
        return $this->datasetDependencies;
    }

    /**
     * @param int $datasetDependencies
     */
    public function setDatasetDependencies($datasetDependencies) {
        $this->datasetDependencies = $datasetDependencies;
    }

    /**
     * @return string
     */
    public function getDashboards() {
        return $this->dashboards;
    }

    /**
     * @param string $dashboards
     */
    public function setDashboards($dashboards) {
        $this->dashboards = $dashboards;
    }

    /**
     * @return int
     */
    public function getDashboardDependencies() {
        return $this->dashboardDependencies;
    }

    /**
     * @param int $dashboardDependencies
     */
    public function setDashboardDependencies($dashboardDependencies) {
        $this->dashboardDependencies = $dashboardDependencies;
    }

    /**
     * @return string
     */
    public function getDatasourceDatasetDependencies() {
        return $this->datasourceDatasetDependencies;
    }

    /**
     * @param string $datasourceDatasetDependencies
     */
    public function setDatasourceDatasetDependencies($datasourceDatasetDependencies) {
        $this->datasourceDatasetDependencies = $datasourceDatasetDependencies;
    }


}
