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
     * Dashboard constructor.
     *
     * @param string $title
     * @param DashboardDatasetInstance[] $datasetInstances
     * @param mixed $displaySettings
     */
    public function __construct($title, $datasetInstances = [], $displaySettings = null, $id = null) {
        parent::__construct($id, $title);
        $this->datasetInstances = $datasetInstances;
        $this->displaySettings = $displaySettings;
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

}