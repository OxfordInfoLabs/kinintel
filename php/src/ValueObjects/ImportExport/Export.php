<?php


namespace Kinintel\ValueObjects\ImportExport;


use Kiniauth\Objects\MetaData\CategorySummary;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;


class Export {

    /**
     * Scope of this export (usually project)
     *
     * @var string
     */
    private $scope;

    /**
     * Title for this export - displayed on import
     *
     * @var string
     */
    private $title;

    /**
     * Date and time when this export was initiated
     *
     * @var \DateTime
     */
    private $exportedDateTime;


    /**
     * Optional version number for this export
     *
     * @var string
     */
    private $version;


    /**
     * Data source instance summaries for export
     *
     * @var DatasourceInstance[]
     */
    private $datasourceInstances = [];

    /**
     * Dataset instance summaries for export
     *
     * @var DatasetInstance[]
     */
    private $datasetInstances = [];


    /**
     * Dashboards for export
     *
     * @var Dashboard[]
     */
    private $dashboards = [];


    /**
     * Alert groups for export
     *
     * @var AlertGroupSummary[]
     */
    private $alertGroups;


    /**
     * Category summaries for export
     *
     * @var CategorySummary[]
     */
    private $categories;


    /**
     * Internal array of datasource keys
     *
     * @var
     */
    private $datasourceKeys = [];


    /**
     * Internal array of dataset ids
     *
     * @var
     */
    private $datasetIds;


    /**
     * Internal array of dashboard ids.
     *
     * @var
     */
    private $dashboardIds;


    // Scope constants for export
    const SCOPE_PROJECT = "PROJECT";
    const SCOPE_ACCOUNT = "ACCOUNT";


    /**
     * Export constructor.
     *
     * @param $scope
     * @param $title
     * @param $datasourceInstances
     * @param $datasetInstances
     * @param $dashboards
     * @param $allAlertGroups
     * @param null $version
     */
    public function __construct($scope, $title, $datasourceInstances, $datasetInstances, $dashboards, $allAlertGroups, $version = null) {
        $this->scope = $scope;
        $this->title = $title;
        $this->version = $version;

        // Process datasource instances
        $this->processDatasourceInstances($datasourceInstances ?? []);

        // Process dataset instances
        $this->processDatasetInstances($datasetInstances ?? []);

        // Process dashboards
        $this->processDashboards($dashboards ?? []);
    }


    /**
     * @return string
     */
    public function getScope() {
        return $this->scope;
    }

    /**
     * @param string $scope
     */
    public function setScope($scope) {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return \DateTime
     */
    public function getExportedDateTime() {
        return $this->exportedDateTime;
    }

    /**
     * @param \DateTime $exportedDateTime
     */
    public function setExportedDateTime($exportedDateTime) {
        $this->exportedDateTime = $exportedDateTime;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version) {
        $this->version = $version;
    }

    /**
     * @return DatasourceInstance[]
     */
    public function getDatasourceInstances() {
        return $this->datasourceInstances;
    }

    /**
     * @param DatasourceInstance[] $datasourceInstances
     */
    public function setDatasourceInstances($datasourceInstances) {
        $this->datasourceInstances = $datasourceInstances;
    }

    /**
     * @return DatasetInstance[]
     */
    public function getDatasetInstances() {
        return $this->datasetInstances;
    }

    /**
     * @param DatasetInstance[] $datasetInstances
     */
    public function setDatasetInstances($datasetInstances) {
        $this->datasetInstances = $datasetInstances;
    }

    /**
     * @return Dashboard
     */
    public function getDashboards() {
        return $this->dashboards;
    }

    /**
     * @param Dashboard $dashboards
     */
    public function setDashboards($dashboards) {
        $this->dashboards = $dashboards;
    }

    /**
     * @return AlertGroupSummary[]
     */
    public function getAlertGroups() {
        return $this->alertGroups;
    }

    /**
     * @param AlertGroupSummary[] $alertGroups
     */
    public function setAlertGroups($alertGroups) {
        $this->alertGroups = $alertGroups;
    }

    /**
     * @return CategorySummary[]
     */
    public function getCategories() {
        return $this->categories;
    }

    /**
     * @param CategorySummary[] $categories
     */
    public function setCategories($categories) {
        $this->categories = $categories;
    }


    /**
     * Process datasource instances to replace keys with placeholders etc.
     *
     * @param DatasourceInstance[] $datasourceInstances
     */
    private function processDatasourceInstances($datasourceInstances) {

        // Stash original datasource keys for tracking hierarchy
        $this->datasourceKeys = ObjectArrayUtils::getMemberValueArrayForObjects("key", $datasourceInstances);

        // Replace keys with md5 hash values
        foreach ($datasourceInstances as $datasourceInstance) {

            // Make a copy of datasource instance
            $datasourceInstance = unserialize(serialize($datasourceInstance));

            $datasourceInstance->setKey(md5("DS::" . $datasourceInstance->getKey()));

            $config = $datasourceInstance->getConfig() ?? [];

            switch ($datasourceInstance->getType()) {
                case "custom":
                case "document":
                    $config["tableName"] = $datasourceInstance->getKey();
                    break;
            }

            $datasourceInstance->setConfig($config);

            $this->datasourceInstances[] = $datasourceInstance;
        }


    }


    /**
     * Process dataset instances to replace keys with placeholders etc.
     *
     * @param DatasetInstance[] $datasetInstances
     */
    private function processDatasetInstances($datasetInstances) {

        // Stash original data set ids for tracking
        $this->datasetIds = ObjectArrayUtils::getMemberValueArrayForObjects("id", $datasetInstances);

        /**
         * Loop through each dataset instance and process accordingly
         */
        foreach ($datasetInstances as $datasetInstance) {

            $datasetInstance = unserialize(serialize($datasetInstance));

            // Update the id
            $datasetInstance->setId(md5("DS::" . $datasetInstance->getId()));

            // Do core logic for dataset instance
            $this->processCoreDatasetInstance($datasetInstance);

            $this->datasetInstances[] = $datasetInstance;


        }

        // Sort the instances so parents come first
        usort($this->datasetInstances, function ($firstDatasetInstance, $secondDatasetInstance) {
            $isParent = ($firstDatasetInstance->getId() == $secondDatasetInstance->getDatasetInstanceId());

            // Check for any join transformations
            $isJoined = false;
            foreach ($secondDatasetInstance->getTransformationInstances() ?? [] as $transformationInstance) {

                /**
                 * @var JoinTransformation $config
                 */
                $config = $transformationInstance->getConfig();

                $isJoined = $isJoined || ($config->getJoinedDataSetInstanceId() == $firstDatasetInstance->getId());

            }

            return $isParent || $isJoined ? -1 : 1;
        });


    }


    /**
     * Process dashboards for export, rewrite ids etc.
     *
     * @param Dashboard[] $dashboards
     */
    private function processDashboards($dashboards) {

        // Stash original data set ids for tracking
        $this->dashboardIds = ObjectArrayUtils::getMemberValueArrayForObjects("id", $dashboards);


        /**
         * Loop through each dataset instance and process accordingly
         */
        foreach ($dashboards as $dashboard) {

            // Make a clone of the dashboard up front.
            $dashboard = unserialize(serialize($dashboard));

            // Update the id
            $dashboard->setId(md5("DB::" . $dashboard->getId()));

            // If an internal parent datasource key, hash it
            if ($dashboard->getParentDashboardId() && in_array($dashboard->getParentDashboardId(), $this->dashboardIds)) {
                $dashboard->setParentDashboardId(md5("DB::" . $dashboard->getParentDashboardId()));
            }


            // Loop through the instances
            foreach ($dashboard->getDatasetInstances() as $instance) {

                // Do core logic for dataset instance
                $this->processCoreDatasetInstance($instance);

            }


            $this->dashboards[] = $dashboard;

        }


        // Sort the instances so parents come first
        usort($this->dashboards, function ($firstDashboard, $secondDashboard) {
            return ($firstDashboard->getId() == $secondDashboard->getParentDashboardId()) ? -1 : 1;
        });

    }

    /**
     *
     * Called from above to process datasource instandces
     *
     *
     * @param $datasetInstance
     */
    private function processCoreDatasetInstance($datasetInstance) {

        // If an internal parent datasource key, hash it
        if ($datasetInstance->getDatasourceInstanceKey() && in_array($datasetInstance->getDatasourceInstanceKey(), $this->datasourceKeys)) {
            $datasetInstance->setDatasourceInstanceKey(md5("DS::" . $datasetInstance->getDatasourceInstanceKey()));
        }

        // If an internal parent dataset id, hash it.
        if ($datasetInstance->getDatasetInstanceId() && in_array($datasetInstance->getDatasetInstanceId(), $this->datasetIds)) {
            $datasetInstance->setDatasetInstanceId(md5("DS::" . $datasetInstance->getDatasetInstanceId()));
        }

        // Loop through transformations and ensure we map any internal ones back to datasources.
        foreach ($datasetInstance->getTransformationInstances() ?? [] as $transformationInstance) {
            if ($transformationInstance->getType() == "join") {
                /**
                 * @var JoinTransformation $config
                 */
                $config = $transformationInstance->getConfig();

                // If an internal datasource referenced remap the key back to hash
                if ($config->getJoinedDataSourceInstanceKey() && in_array($config->getJoinedDataSourceInstanceKey(), $this->datasourceKeys)) {
                    $config->setJoinedDataSourceInstanceKey(md5("DS::" . $config->getJoinedDataSourceInstanceKey()));
                }

                // If an internal dataset referenced remap the id back to hash
                if ($config->getJoinedDataSetInstanceId() && in_array($config->getJoinedDataSetInstanceId(), $this->datasetIds)) {
                    $config->setJoinedDataSetInstanceId(md5("DS::" . $config->getJoinedDataSetInstanceId()));
                }

            }
        }
    }

}