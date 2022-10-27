<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;


use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Datasource\DatasourceInstance;

/**
 *
 *
 * Class DatasourceImportProcessorConfiguration
 * @package Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport
 */
class TabularDatasourceImportProcessorConfiguration {

    /**
     * The key of the datasource to use as the seed for this import operation
     *
     * @var string
     * @requiredEither sourceDatasourceKeys,sourceDataset
     */
    private $sourceDatasourceKey;

    /**
     * Any array of keys if multiple datasources are being imported.
     * In order for this to work all data sources must be of the same format as they
     * will be effectively unioned together and imported as one.
     *
     * @var string[]
     */
    private $sourceDatasourceKeys;


    /**
     * @var DatasetInstance
     */
    private $sourceDataset;


    /**
     * Array of parameter sets to pass to repeated calls to the source datasource / dataset
     * Used to allow the same single source to be called with different values as required
     *
     * @var array
     */
    private $sourceParameterSets = [];


    /**
     * Mapping of target existing values to source parameters.  Very useful when we want to seed reads
     * based upon last stored values.
     *
     * @var TargetSourceParameterMapping
     */
    private $targetSourceParameterMapping;

    /**
     * An array of target datasources being fed by the source datasource.
     *
     * @var TargetDatasource[]
     * @required
     */
    private $targetDatasources;


    /**
     * Chunksize to use for reading data from source
     *
     * @var integer
     */
    private $sourceReadChunkSize = null;


    /**
     * Update chunksize
     *
     * @var int
     */
    private $targetWriteChunkSize = 25;

    /**
     * DatasourceImportProcessorConfiguration constructor.
     *
     * @param string $sourceDatasourceKey
     * @param TargetDatasource[] $targetDatasources
     * @param string[] $sourceDatasourceKeys
     * @param DatasetInstance $sourceDataset
     * @param integer $sourceReadChunkSize
     * @param integer $targetWriteChunkSize
     * @param TargetSourceParameterMapping $targetSourceParameterMapping
     */
    public function __construct($sourceDatasourceKey = null, $targetDatasources = [], $sourceDatasourceKeys = [], $sourceDataset = null, $sourceReadChunkSize = null, $targetWriteChunkSize = 25, $targetSourceParameterMapping = null) {
        $this->sourceDatasourceKey = $sourceDatasourceKey;
        $this->targetDatasources = $targetDatasources;
        $this->sourceDatasourceKeys = $sourceDatasourceKeys;
        $this->sourceDataset = $sourceDataset;
        $this->sourceReadChunkSize = $sourceReadChunkSize;
        $this->targetWriteChunkSize = $targetWriteChunkSize;
        $this->targetSourceParameterMapping = $targetSourceParameterMapping;
    }


    /**
     * @return string
     */
    public function getSourceDatasourceKey() {
        return $this->sourceDatasourceKey;
    }

    /**
     * @param string $sourceDatasourceKey
     */
    public function setSourceDatasourceKey($sourceDatasourceKey) {
        $this->sourceDatasourceKey = $sourceDatasourceKey;
    }

    /**
     * @return string[]
     */
    public function getSourceDatasourceKeys() {
        return $this->sourceDatasourceKeys;
    }

    /**
     * @param string[] $sourceDatasourceKeys
     */
    public function setSourceDatasourceKeys($sourceDatasourceKeys) {
        $this->sourceDatasourceKeys = $sourceDatasourceKeys;
    }

    /**
     * @return DatasetInstance
     */
    public function getSourceDataset() {
        return $this->sourceDataset;
    }

    /**
     * @param DatasetInstance $sourceDataset
     */
    public function setSourceDataset($sourceDataset) {
        $this->sourceDataset = $sourceDataset;
    }

    /**
     * @return array
     */
    public function getSourceParameterSets() {
        return $this->sourceParameterSets;
    }

    /**
     * @param array $sourceParameterSets
     */
    public function setSourceParameterSets($sourceParameterSets) {
        $this->sourceParameterSets = $sourceParameterSets;
    }


    /**
     * @return TargetDatasource[]
     */
    public function getTargetDatasources() {
        return $this->targetDatasources;
    }

    /**
     * @param TargetDatasource[] $targetDatasources
     */
    public function setTargetDatasources($targetDatasources) {
        $this->targetDatasources = $targetDatasources;
    }

    /**
     * @return int
     */
    public function getSourceReadChunkSize() {
        return $this->sourceReadChunkSize;
    }

    /**
     * @param int $sourceReadChunkSize
     */
    public function setSourceReadChunkSize($sourceReadChunkSize) {
        $this->sourceReadChunkSize = $sourceReadChunkSize;
    }

    /**
     * @return int
     */
    public function getTargetWriteChunkSize() {
        return $this->targetWriteChunkSize;
    }

    /**
     * @param int $targetWriteChunkSize
     */
    public function setTargetWriteChunkSize($targetWriteChunkSize) {
        $this->targetWriteChunkSize = $targetWriteChunkSize;
    }

    /**
     * @return TargetSourceParameterMapping
     */
    public function getTargetSourceParameterMapping() {
        return $this->targetSourceParameterMapping;
    }

    /**
     * @param TargetSourceParameterMapping $targetSourceParameterMapping
     */
    public function setTargetSourceParameterMapping($targetSourceParameterMapping) {
        $this->targetSourceParameterMapping = $targetSourceParameterMapping;
    }


}