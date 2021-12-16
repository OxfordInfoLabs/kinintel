<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;


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
     * @requiredEither sourceDatasourceKeys,sourceDatasource
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
     * @var DatasourceInstance
     */
    private $sourceDatasource;


    /**
     * An array of target datasources being fed by the source datasource.
     *
     * @var TargetDatasource[]
     * @required
     */
    private $targetDatasources;

    /**
     * DatasourceImportProcessorConfiguration constructor.
     *
     * @param string $sourceDatasourceKey
     * @param TargetDatasource[] $targetDatasources
     * @param string[] $sourceDatasourceKeys
     * @param DatasourceInstance $sourceDatasource
     */
    public function __construct($sourceDatasourceKey = null, $targetDatasources = [], $sourceDatasourceKeys = [], $sourceDatasource = null) {
        $this->sourceDatasourceKey = $sourceDatasourceKey;
        $this->targetDatasources = $targetDatasources;
        $this->sourceDatasourceKeys = $sourceDatasourceKeys;
        $this->sourceDatasource = $sourceDatasource;
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
     * @return DatasourceInstance
     */
    public function getSourceDatasource() {
        return $this->sourceDatasource;
    }

    /**
     * @param DatasourceInstance $sourceDatasource
     */
    public function setSourceDatasource($sourceDatasource) {
        $this->sourceDatasource = $sourceDatasource;
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


}