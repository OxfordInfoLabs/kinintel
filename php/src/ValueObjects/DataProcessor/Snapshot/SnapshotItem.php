<?php

namespace Kinintel\ValueObjects\DataProcessor\Snapshot;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\ValueObjects\DataProcessor\DataProcessorItem;

class SnapshotItem {

    /**
     * @var string
     */
    private $key;


    /**
     * @var string
     */
    private $title;


    /**
     * Trigger (adhoc or scheduled)
     *
     * @var string
     */
    private $trigger;


    /**
     * Matching dataset management key
     *
     * @var string
     */
    private $datasetManagementKey;


    /**
     * Running status
     *
     * @var string
     */
    private $status;

    /**
     * Construct a new snapshot item
     *
     * @param string $key
     * @param string $title
     * @param string $trigger
     * @param string $datasetManagementKey
     * @param string $status
     */
    public function __construct($key, $title, $trigger, $datasetManagementKey, $status) {
        $this->key = $key;
        $this->title = $title;
        $this->trigger = $trigger;
        $this->datasetManagementKey = $datasetManagementKey;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getTrigger() {
        return $this->trigger;
    }

    /**
     * @return string
     */
    public function getDatasetManagementKey() {
        return $this->datasetManagementKey;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param DataProcessorInstance $dataProcessorInstance
     * @return SnapshotItem
     */
    public static function fromDataProcessorInstance($dataProcessorInstance) {

        return new SnapshotItem($dataProcessorInstance->getKey(), $dataProcessorInstance->getTitle(),
            $dataProcessorInstance->getTrigger(), $dataProcessorInstance->getRelatedObjectKey(), $dataProcessorInstance->getScheduledTask()?->getStatus());
    }


}