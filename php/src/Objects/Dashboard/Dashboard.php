<?php

namespace Kinintel\Objects\Dashboard;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Dashboard - encodes one or more dataset instances along with display configuration
 * and global transformations to be applied to one or more datasets for e.g. global filtering etc.
 *
 * Class Dashboard
 * @table ki_dashboard
 * @generate
 */
class Dashboard extends ActiveRecord {

    /**
     * Primary key for this dashboard
     *
     * @var integer
     */
    private $id;


    /**
     * Title for the dashboard
     *
     * @var string
     * @required
     */
    private $title;


    /**
     * Attached dataset instances
     *
     * @var DashboardDatasetInstance[]
     * @oneToMany
     * @childJoinColumns dashboard_id
     */
    private $datasetInstances;


    /**
     * Display settings
     *
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    private $displaySettings;

    /**
     * Dashboard constructor.
     *
     * @param string $title
     * @param DashboardDatasetInstance[] $datasetInstances
     * @param mixed $displaySettings
     */
    public function __construct($title, $datasetInstances = [], $displaySettings = null) {
        $this->title = $title;
        $this->datasetInstances = $datasetInstances;
        $this->displaySettings = $displaySettings;
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