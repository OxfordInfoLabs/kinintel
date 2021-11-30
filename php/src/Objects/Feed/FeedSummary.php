<?php


namespace Kinintel\Objects\Feed;


use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;

class FeedSummary extends ActiveRecord {

    /**
     * Unique primary key
     *
     * @var integer
     */
    protected $id;

    /**
     * Relative path to the feed used to resolve this feed based on the incoming URL
     *
     * @var string
     */
    protected $path;


    /**
     * Id of dataset which this feed calls
     *
     * @var integer
     */
    protected $datasetInstanceId;

    /**
     * List of parameter names which should be exposed as part of this feed.
     *
     * @var string[]
     * @json
     */
    protected $exposedParameterNames;


    /**
     * Exporter key used for defining the exporter
     *
     * @var string
     */
    protected $exporterKey;

    /**
     * Exporter configuration if required for the specified exporter used for this feed.
     *
     * @var mixed
     * @json
     */
    protected $exporterConfiguration;


    /**
     * @var DatasetInstanceSearchResult
     * @manyToOne
     * @readOnly
     * @parentJoinColumns dataset_instance_id
     */
    protected $datasetLabel;

    /**
     * FeedSummary constructor.
     * @param string $path
     * @param int $datasetInstanceId
     * @param string[] $exposedParameterNames
     * @param string $exporterKey
     * @param mixed $exporterConfiguration
     */
    public function __construct($path, $datasetInstanceId, $exposedParameterNames, $exporterKey, $exporterConfiguration, $id = null) {
        $this->path = $path;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->exposedParameterNames = $exposedParameterNames;
        $this->exporterKey = $exporterKey;
        $this->exporterConfiguration = $exporterConfiguration;
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
    public function getPath() {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * @return int
     */
    public function getDatasetInstanceId() {
        return $this->datasetInstanceId;
    }

    /**
     * @param int $datasetInstanceId
     */
    public function setDatasetInstanceId($datasetInstanceId) {
        $this->datasetInstanceId = $datasetInstanceId;
    }

    /**
     * @return string[]
     */
    public function getExposedParameterNames() {
        return $this->exposedParameterNames;
    }

    /**
     * @param string[] $exposedParameterNames
     */
    public function setExposedParameterNames($exposedParameterNames) {
        $this->exposedParameterNames = $exposedParameterNames;
    }

    /**
     * @return string
     */
    public function getExporterKey() {
        return $this->exporterKey;
    }

    /**
     * @param string $exporterKey
     */
    public function setExporterKey($exporterKey) {
        $this->exporterKey = $exporterKey;
    }

    /**
     * @return mixed
     */
    public function getExporterConfiguration() {
        return $this->exporterConfiguration;
    }

    /**
     * @param mixed $exporterConfiguration
     */
    public function setExporterConfiguration($exporterConfiguration) {
        $this->exporterConfiguration = $exporterConfiguration;
    }

    /**
     * @return DatasetInstanceSearchResult
     */
    public function getDatasetLabel() {
        return $this->datasetLabel;
    }

    /**
     * @param DatasetInstanceSearchResult $datasetLabel
     */
    public function setDatasetLabel($datasetLabel) {
        $this->datasetLabel = $datasetLabel;
    }


}