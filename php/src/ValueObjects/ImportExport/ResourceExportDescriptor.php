<?php


namespace Kinintel\ValueObjects\ImportExport;


class ResourceExportDescriptor {

    /**
     * Either project or account level scope for resource export
     *
     * @var string
     */
    private $scope;


    /**
     * Id for the scope (project key or account id)
     *
     * @var mixed
     */
    private $scopeId;


    /**
     * Title written out to the export to identify on import.
     *
     * @var string
     */
    private $title;


    /**
     * Optional version can be supplied for this export.
     *
     * @var string
     */
    private $version;

    /**
     * Array of datasource instance keys to export
     *
     * @var string[]
     */
    private $datasourceInstanceKeys;


    /**
     * @var integer[]
     */
    private $datasetInstanceIds;


    /**
     * @var integer[]
     */
    private $dashboardIds;


    // Scope constants for export
    const SCOPE_PROJECT = "PROJECT";
    const SCOPE_ACCOUNT = "ACCOUNT";

    /**
     * ResourceExportDescriptor constructor.
     * @param string $scope
     * @param mixed $scopeId
     * @param string[] $datasourceInstanceKeys
     * @param integer[] $datasetInstanceIds
     * @param integer[] $dashboardIds
     */
    public function __construct($scopeId, $datasourceInstanceKeys = [],
                                $datasetInstanceIds = [], $dashboardIds = [],
                                $title = null, $version = null,
                                $scope = self::SCOPE_PROJECT) {
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $this->datasourceInstanceKeys = $datasourceInstanceKeys;
        $this->datasetInstanceIds = $datasetInstanceIds;
        $this->dashboardIds = $dashboardIds;
        $this->title = $title;
        $this->version = $version;
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
     * @return mixed
     */
    public function getScopeId() {
        return $this->scopeId;
    }

    /**
     * @param mixed $scopeId
     */
    public function setScopeId($scopeId) {
        $this->scopeId = $scopeId;
    }

    /**
     * @return string[]
     */
    public function getDatasourceInstanceKeys() {
        return $this->datasourceInstanceKeys;
    }

    /**
     * @param string[] $datasourceInstanceKeys
     */
    public function setDatasourceInstanceKeys($datasourceInstanceKeys) {
        $this->datasourceInstanceKeys = $datasourceInstanceKeys;
    }

    /**
     * @return integer[]
     */
    public function getDatasetInstanceIds() {
        return $this->datasetInstanceIds;
    }

    /**
     * @param integer[] $datasetInstanceIds
     */
    public function setDatasetInstanceIds($datasetInstanceIds) {
        $this->datasetInstanceIds = $datasetInstanceIds;
    }

    /**
     * @return integer[]
     */
    public function getDashboardIds() {
        return $this->dashboardIds;
    }

    /**
     * @param integer[] $dashboardIds
     */
    public function setDashboardIds($dashboardIds) {
        $this->dashboardIds = $dashboardIds;
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


}