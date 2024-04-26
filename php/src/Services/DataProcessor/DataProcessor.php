<?php


namespace Kinintel\Services\DataProcessor;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;

/**
 * @implementation tabulardatasourcechangetracking \Kinintel\Services\DataProcessor\DatasourceImport\TabularDatasourceChangeTrackingProcessor
 * @implementation tabulardatasourceimport \Kinintel\Services\DataProcessor\DatasourceImport\TabularDatasourceImportProcessor
 * @implementation tabulardatasourceaggregating \Kinintel\Services\DataProcessor\DatasourceImport\TabularDatasourceAggregatingProcessor
 * @implementation tabulardatasetsnapshot \Kinintel\Services\DataProcessor\DatasetSnapshot\TabularDatasetSnapshotProcessor
 * @implementation tabulardatasetincrementalsnapshot \Kinintel\Services\DataProcessor\DatasetSnapshot\TabularDatasetIncrementalSnapshotProcessor
 * @implementation vectorembedding \Kinintel\Services\DataProcessor\VectorDataset\VectorDatasetProcessor
 * @implementation distanceandclustering \Kinintel\Services\DataProcessor\Analysis\StatisticalAnalysis\DistanceAndClusteringProcessor
 * @implementation sqlquery \Kinintel\Services\DataProcessor\Query\SQLQueryDataProcessor
 * @implementation querycaching \Kinintel\Services\DataProcessor\Query\QueryCachingDataProcessor
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
     * to the config class declared above.  This is called to run the processor
     *
     * @param DataProcessorInstance $instance
     */
    public function process($instance);


    /**
     * Save hook, called when an instance is saved.  Useful to modify state
     *
     * @param DataProcessorInstance $instance
     */
    public function onInstanceSave($instance);

    /**
     * Delete hook, called when an instance is deleted - useful to clean up database artifacts etc.
     *
     * @param DataProcessorInstance $instance
     */
    public function onInstanceDelete($instance);


    /**
     * Hook called when a related object is updated.  Useful if we need to modify state e.g. schema
     * when things change.
     *
     * @param DataProcessorInstance $instance
     * @param mixed $relatedObject
     */
    public function onRelatedObjectSave($instance, $relatedObject);

}