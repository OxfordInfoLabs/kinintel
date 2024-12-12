<?php


namespace Kinintel\Objects\Dashboard;


use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\MetaData\TagSummary;
use Kinintel\ValueObjects\Dashboard\DashboardExternalSettings;

class DashboardSummary extends DashboardSearchResult {


    /**
     * Attached dataset instances
     *
     * @var DashboardDatasetInstance[]
     * @oneToMany
     * @childJoinColumns dashboard_id
     */
    protected $datasetInstances;


    /**
     * Layout settings
     *
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    protected $layoutSettings;


    /**
     * Display settings
     *
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    protected $displaySettings;


    /**
     * @var boolean
     */
    protected $external;


    /**
     * @var DashboardExternalSettings
     * @json
     * @sqlType LONGTEXT
     */
    protected $externalSettings;


    /**
     * @var boolean
     */
    protected $hiddenFromListings;


    /**
     * Array of tag keys associated with this instance summary if required
     *
     * @var TagSummary[]
     */
    protected $tags = [];


    /**
     * Alerts enabled boolean
     *
     * @var boolean
     */
    protected $alertsEnabled = true;


    /**
     * @var boolean
     */
    private $readOnly;

    /**
     * Dashboard constructor.
     *
     * @param string $title
     * @param DashboardDatasetInstance[] $datasetInstances
     * @param mixed $displaySettings
     * @param mixed $layoutSettings
     * @param boolean $alertsEnabled
     * @param boolean $external
     * @param DashboardExternalSettings $externalSettings
     * @param boolean $hiddenFromListings
     * @param string $summary
     * @param string $description
     * @param CategorySummary[] $categories
     */
    public function __construct($title, $datasetInstances = [], $displaySettings = null, $layoutSettings = null, $alertsEnabled = null, $external = null, $externalSettings = null, $hiddenFromListings = false, $summary = null, $description = null, $categories = [], $id = null, $readOnly = false, $parentDashboardId = null) {
        parent::__construct($id, $title, $summary, $description, $categories, $parentDashboardId);
        $this->datasetInstances = $datasetInstances;
        $this->displaySettings = $displaySettings;
        $this->layoutSettings = $layoutSettings;
        $this->alertsEnabled = $alertsEnabled;
        $this->readOnly = $readOnly;
        $this->external = $external;
        $this->externalSettings = $externalSettings ?? new DashboardExternalSettings();
        $this->hiddenFromListings = $hiddenFromListings;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }



    /**
     * @return DashboardDatasetInstance[]
     */
    public function getDatasetInstances() {
        return $this->datasetInstances;
    }

    /**
     * @param DashboardDatasetInstance[] $datasetInstances
     */
    public function setDatasetInstances($datasetInstances) {
        $this->datasetInstances = $datasetInstances;
    }

    /**
     * @return mixed
     */
    public function getLayoutSettings() {
        return $this->layoutSettings;
    }

    /**
     * @param mixed $layoutSettings
     */
    public function setLayoutSettings($layoutSettings) {
        $this->layoutSettings = $layoutSettings;
    }


    /**
     * @return mixed
     */
    public function getDisplaySettings() {
        return $this->displaySettings;
    }

    /**
     * @param mixed $displaySettings
     */
    public function setDisplaySettings($displaySettings) {
        $this->displaySettings = $displaySettings;
    }

    /**
     * @return TagSummary[]
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @param TagSummary[] $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }


    /**
     * @return bool
     */
    public function isAlertsEnabled() {
        return $this->alertsEnabled;
    }

    /**
     * @param bool $alertsEnabled
     */
    public function setAlertsEnabled($alertsEnabled) {
        $this->alertsEnabled = $alertsEnabled;
    }

    /**
     * @return boolean
     */
    public function isExternal() {
        return $this->external;
    }

    /**
     * @param boolean $external
     */
    public function setExternal($external) {
        $this->external = $external;
    }

    /**
     * @return DashboardExternalSettings
     */
    public function getExternalSettings() {
        return $this->externalSettings ?? new DashboardExternalSettings();
    }

    /**
     * @param DashboardExternalSettings $externalSettings
     */
    public function setExternalSettings($externalSettings) {
        $this->externalSettings = $externalSettings;
    }

    /**
     * @return bool
     */
    public function isReadOnly() {
        return $this->readOnly;
    }

    /**
     * @return bool
     */
    public function isHiddenFromListings() {
        return $this->hiddenFromListings;
    }

    /**
     * @param bool $hiddenFromListings
     */
    public function setHiddenFromListings($hiddenFromListings) {
        $this->hiddenFromListings = $hiddenFromListings;
    }


}
