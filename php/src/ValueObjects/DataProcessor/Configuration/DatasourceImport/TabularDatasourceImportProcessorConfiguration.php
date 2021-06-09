<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;


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
     * @required
     */
    private $sourceDatasourceKey;


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
     */
    public function __construct($sourceDatasourceKey = null, $targetDatasources = []) {
        $this->sourceDatasourceKey = $sourceDatasourceKey;
        $this->targetDatasources = $targetDatasources;
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