<?php


namespace Kinintel\Objects\Dashboard;


use Kinikit\Persistence\ORM\ActiveRecord;

class DashboardSummary extends ActiveRecord {

    /**
     * Primary key for this dashboard
     *
     * @var integer
     */
    protected $id;


    /**
     * Title for the dashboard
     *
     * @var string
     * @required
     */
    protected $title;


    /**
     * Attached dataset instances
     *
     * @var DashboardDatasetInstance[]
     * @oneToMany
     * @childJoinColumns dashboard_id
     */
    protected $datasetInstances;


    /**
     * Display settings
     *
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    protected $displaySettings;

    /**
     * Dashboard constructor.
     *
     * @param string $title
     * @param DashboardDatasetInstance[] $datasetInstances
     * @param mixed $displaySettings
     */
    public function __construct($title, $datasetInstances = [], $displaySettings = null, $id = null) {
        $this->title = $title;
        $this->datasetInstances = $datasetInstances;
        $this->displaySettings = $displaySettings;
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
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
    public function getDisplaySettings() {
        return $this->displaySettings;
    }

    /**
     * @param mixed $displaySettings
     */
    public function setDisplaySettings($displaySettings) {
        $this->displaySettings = $displaySettings;
    }

}