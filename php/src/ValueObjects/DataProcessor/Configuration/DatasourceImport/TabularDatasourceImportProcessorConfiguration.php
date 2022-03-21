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
     * An array of target datasources being fed by the source datasource.
     *
     * @var TargetDatasource[]
     * @required
     */
    private $targetDatasources;


    /**
     * Chunksize to use for reading data from
     *
     * @var integer
     */
    private $sourceReadChunkSize = 500;


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
     */
    public function __construct($sourceDatasourceKey = null, $targetDatasources = [], $sourceDatasourceKeys = [], $sourceDataset = null, $sourceReadChunkSize = 500, $targetWriteChunkSize = 25) {
        $this->sourceDatasourceKey = $sourceDatasourceKey;
        $this->targetDatasources = $targetDatasources;
        $this->sourceDatasourceKeys = $sourceDatasourceKeys;
        $this->sourceDataset = $sourceDataset;
        $this->sourceReadChunkSize = $sourceReadChunkSize;
        $this->targetWriteChunkSize = $targetWriteChunkSize;
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


}