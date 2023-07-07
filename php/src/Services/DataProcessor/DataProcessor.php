<?php


namespace Kinintel\Services\DataProcessor;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;

/**
 * @implementation tabulardatasourcechangetracking \Kinintel\Services\DataProcessor\DatasourceImport\TabularDatasourceChangeTrackingProcessor
 * @implementation tabulardatasourceimport \Kinintel\Services\DataProcessor\DatasourceImport\TabularDatasourceImportProcessor
 * @implementation tabulardatasourceaggregating \Kinintel\Services\DataProcessor\DatasourceImport\TabularDatasourceAggregatingProcessor
 * @implementation tabulardatasetsnapshot \Kinintel\Services\DataProcessor\DatasetSnapshot\TabularDatasetSnapshotProcessor
 * @implementation tabulardatasetincrementalsnapshot \Kinintel\Services\DataProcessor\DatasetSnapshot\TabularDatasetIncrementalSnapshotProcessor
 * @implementation distanceandclustering \Kinintel\Services\DataProcessor\Analysis\StatisticalAnalysis\DistanceAndClusteringProcessor
 * @implementation sqlquery \Kinintel\Services\DataProcessor\Query\SQLQueryDataProcessor
 * @implementation multi \Kinintel\Services\DataProcessor\Multi\MultiDataProcessor
 */
interface DataProcessor {

    /**
     * Get the config class expected by the process method.  Can be null if
     * no config required
     *
     * @return string
     */
    public function getConfigClass();


    /**
     * Main process method.  Receives config which should be typed according
     * to the config class declared above.
     * @param DataProcessorInstance $instance
     */
    public function process($instance);


}