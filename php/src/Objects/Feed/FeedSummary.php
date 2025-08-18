<?php


namespace Kinintel\Objects\Feed;


use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\ValueObjects\Feed\FeedWebsiteConfig;

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
     * If set, additive adhoc filtering is enabled via request parameters
     *
     * @var bool
     */
    protected $adhocFiltering;


    /**
     * If set, advanced querying is enabled.
     *
     * @var bool
     */
    protected $advancedQuerying;

    /**
     * Set the name of the advanced query parameter name.
     *
     * @var string
     */
    protected $advancedQueryParameterName = "query";


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
     * Cache time for this feed in seconds.
     *
     * @var int
     */
    protected $cacheTimeSeconds = 0;


    /**
     * @var DatasetInstanceSearchResult
     * @manyToOne
     * @readOnly
     * @parentJoinColumns dataset_instance_id
     */
    protected $datasetLabel;


    /**
     * @var FeedWebsiteConfig
     * @json
     * @sqlType LONGTEXT
     */
    protected $websiteConfig;


    /**
     * FeedSummary constructor.
     *
     * @param string $path
     * @param int $datasetInstanceId
     * @param string[] $exposedParameterNames
     * @param string $exporterKey
     * @param mixed $exporterConfiguration
     * @param bool $adhocFiltering
     * @param bool $advancedQuerying
     * @param string $advancedQueryParameterName
     * @param int $cacheTimeSeconds
     * @param FeedWebsiteConfig $websiteConfig
     */
    public function __construct($path, $datasetInstanceId, $exposedParameterNames, $exporterKey, $exporterConfiguration, $adhocFiltering = false, $advancedQuerying = false, $advancedQueryParameterName = "query", $cacheTimeSeconds = 0, $websiteConfig = null, $id = null) {
        $this->path = $path;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->exposedParameterNames = $exposedParameterNames;
        $this->exporterKey = $exporterKey;
        $this->exporterConfiguration = $exporterConfiguration;
        $this->id = $id;
        $this->cacheTimeSeconds = $cacheTimeSeconds;
        $this->websiteConfig = $websiteConfig ?? new FeedWebsiteConfig();
        $this->adhocFiltering = $adhocFiltering;
        $this->advancedQuerying = $advancedQuerying;
        $this->advancedQueryParameterName = $advancedQueryParameterName;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): void {
        $this->id = $id;
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
     * @return bool
     */
    public function isAdhocFiltering(): ?bool {
        return $this->adhocFiltering;
    }

    /**
     * @param bool $adhocFiltering
     */
    public function setAdhocFiltering(?bool $adhocFiltering): void {
        $this->adhocFiltering = $adhocFiltering;
    }

    /**
     * @return bool
     */
    public function isAdvancedQuerying(): ?bool {
        return $this->advancedQuerying;
    }

    /**
     * @param bool $advancedQuerying
     */
    public function setAdvancedQuerying(?bool $advancedQuerying): void {
        $this->advancedQuerying = $advancedQuerying;
    }


    /**
     * @return string
     */
    public function getAdvancedQueryParameterName(): ?string {
        return $this->advancedQueryParameterName;
    }

    /**
     * @param string $advancedQueryParameterName
     */
    public function setAdvancedQueryParameterName(?string $advancedQueryParameterName): void {
        $this->advancedQueryParameterName = $advancedQueryParameterName;
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

    /**
     * @return int
     */
    public function getCacheTimeSeconds() {
        return $this->cacheTimeSeconds;
    }

    /**
     * @param int $cacheTimeSeconds
     */
    public function setCacheTimeSeconds($cacheTimeSeconds) {
        $this->cacheTimeSeconds = $cacheTimeSeconds;
    }

    /**
     * @return FeedWebsiteConfig
     */
    public function getWebsiteConfig() {
        return $this->websiteConfig ?? new FeedWebsiteConfig();
    }

    /**
     * @param FeedWebsiteConfig $websiteConfig
     */
    public function setWebsiteConfig($websiteConfig) {
        $this->websiteConfig = $websiteConfig;
    }


}