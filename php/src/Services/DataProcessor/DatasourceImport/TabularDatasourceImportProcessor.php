<?php


namespace Kinintel\Services\DataProcessor\DatasourceImport;


use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceImportProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TargetDatasource;


class TabularDatasourceImportProcessor implements DataProcessor {

    /**
     * @var DatasourceService
     */
    private $datasourceService;


    /**
     * @var integer
     */
    private $chunkSize;

    /**
     * DatasourceImportProcessor constructor.
     *
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService, $chunkSize = 500) {
        $this->datasourceService = $datasourceService;
        $this->chunkSize = $chunkSize;
    }


    /**
     * Return the configuration class for the import processor
     *
     * @return string
     */
    public function getConfigClass() {
        return TabularDatasourceImportProcessorConfiguration::class;
    }

    /**
     * Process a datasource import
     *
     * @param TabularDatasourceImportProcessorConfiguration $config
     */
    public function process($config = null) {

        // Check all is well
        $sourceDatasource = $this->datasourceService->getDataSourceInstanceByKey($config->getSourceDatasourceKey())->returnDataSource();

        /**
         * Loop through each target datasource and ensure we can update first up
         */
        $targetDatasources = [];
        foreach ($config->getTargetDatasources() as $targetDatasourceObj) {
            $targetDatasource = $this->datasourceService->getDataSourceInstanceByKey($targetDatasourceObj->getKey())->returnDataSource();
            if (!($targetDatasource instanceof UpdatableDatasource)) {
                throw new DatasourceNotUpdatableException($targetDatasource);
            }
            $targetDatasources[] = [$targetDatasourceObj, $targetDatasource];
        }

        /**
         * @var TabularDataset $sourceDataset
         */
        $sourceDataset = $sourceDatasource->materialise();

        if (!($sourceDataset instanceof TabularDataset)) {
            throw new UnsupportedDatasetException("The source datasource supplied to the processor must materialise to a tabular data set");
        }

        $fields = $sourceDataset->getColumns();

        // Chunk the results according to chunk size for scalability.
        $read = 0;
        $dataItems = [];
        while ($dataItem = $sourceDataset->nextDataItem()) {
            $dataItems[] = $dataItem;
            $read++;
            if ($read % $this->chunkSize == 0) {
                $this->processTargetChunkResults($targetDatasources, $fields, $dataItems);
                $dataItems = [];
            }
        }

        // Process remainder if required
        if (sizeof($dataItems))
            $this->processTargetChunkResults($targetDatasources, $fields, $dataItems);


    }

    // Process target chunk results
    private function processTargetChunkResults($targetDatasources, $fields, $chunkedResults) {

        /**
         * Update all targets with chunked results
         *
         * @var TargetDatasource $targetObject
         * @var UpdatableDatasource $targetDatasource
         */
        foreach ($targetDatasources as list($targetObject, $targetDatasource)) {

            // Use fields on the target mapping object if set otherwise default to incoming fields
            $fields = $targetObject->getFields() ?? $fields;

            // Create a new data set for this target transaction
            $dataset = new ArrayTabularDataset($fields, $chunkedResults);

            // Replace results
            $targetDatasource->update($dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);
        }

    }

}