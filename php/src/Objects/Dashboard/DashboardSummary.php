<?php


namespace Kinintel\Objects\Dashboard;


use Kiniauth\Objects\MetaData\TagSummary;
use Kinikit\Persistence\ORM\ActiveRecord;

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
     * Array of tag keys associated with this instance summary if required
     *
     * @var TagSummary[]
     */
    protected $tags = [];


    /**
     * Alerts enabled boolean
     *
     * @var bool
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
     */
    public function __construct($title, $datasetInstances = [], $displaySettings = null, $layoutSettings = null, $alertsEnabled = null, $id = null, $readOnly = false) {
        parent::__construct($id, $title);
        $this->datasetInstances = $datasetInstances;
        $this->displaySettings = $displaySettings;
        $this->layoutSettings = $layoutSettings;
        $this->alertsEnabled = $alertsEnabled;
        $this->readOnly = $readOnly;
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
     * @return bool
     */
    public function isReadOnly() {
        return $this->readOnly;
    }

}
