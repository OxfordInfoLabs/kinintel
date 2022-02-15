<?php


namespace Kinintel\Services\DataProcessor\DatasourceImport;


use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceImportProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TargetDatasource;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TargetField;
use Kinintel\ValueObjects\Dataset\Field;


class TabularDatasourceImportProcessor implements DataProcessor {

    /**
     * @var DatasourceService
     */
    private $datasourceService;


    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * @var integer
     */
    private $chunkSize;

    /**
     * DatasourceImportProcessor constructor.
     *
     * @param DatasourceService $datasourceService
     * @param DatasetService $datasetService
     * @param int $chunkSize
     */
    public function __construct($datasourceService, $datasetService, $chunkSize = 500) {
        $this->datasourceService = $datasourceService;
        $this->datasetService = $datasetService;
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


        if ($config->getSourceDataset()) {

            $offset = 0;
            do {

                $sourceDataset = $this->datasetService->getEvaluatedDataSetForDataSetInstance($config->getSourceDataset(), null, null, $offset, $this->chunkSize);

                $allData = $sourceDataset->getAllData();


                $this->populateTargetDatasources(new ArrayTabularDataset($sourceDataset->getColumns(), $allData), $targetDatasources);
                $offset += $this->chunkSize;
            } while (sizeof($allData) > 0);


        } else {

            // Grab all data sources
            $sourceDatasources = [];

            $sourceDatasourceKeys = $config->getSourceDatasourceKey() ? [$config->getSourceDatasourceKey()] : $config->getSourceDatasourceKeys();

            foreach ($sourceDatasourceKeys as $datasourceKey) {
                $sourceDatasources[] = $this->datasourceService->getDataSourceInstanceByKey($datasourceKey)->returnDataSource();
            }


            // Process all applicable data sources
            foreach ($sourceDatasources as $sourceDatasource) {

                /**
                 * @var TabularDataset $sourceDataset
                 */
                $sourceDataset = $sourceDatasource->materialise();

                // Populate the target datasources
                $this->populateTargetDatasources($sourceDataset, $targetDatasources);

            }
        }

    }


    /**
     * Populate target datasources from a source dataset
     *
     *
     * @param TabularDataset $sourceDataset
     * @param array $targetDatasources
     */
    private function populateTargetDatasources($sourceDataset, $targetDatasources) {

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

            // If explicit fields we need to do additional processing to map any fields.
            if ($targetObject->getFields()) {
                foreach ($chunkedResults as $index => $chunkedResult) {
                    foreach ($targetObject->getFields() as $field) {
                        if ($field instanceof TargetField) {
                            $dataValue = $chunkedResult[$field->getName()] ?? null;

                            // Remove original if target set
                            if ($field->getTargetName()) {
                                unset($chunkedResult[$field->getName()]);
                            }

                            $chunkedResult[$field->getTargetName() ?? $field->getName()] = $field->returnMappedValue($dataValue);
                        }
                    }
                    $chunkedResults[$index] = $chunkedResult;
                }

                // Assemble new fields
                $fields = [];
                foreach ($targetObject->getFields() as $field) {
                    if ($field instanceof TargetField) {
                        $fields[] = new Field($field->getTargetName() ?? $field->getName());
                    } else {
                        $fields[] = $field;
                    }
                }
            }

            // Create a new data set for this target transaction
            $dataset = new ArrayTabularDataset($fields, $chunkedResults);

            // Replace results
            $targetDatasource->update($dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);
        }

    }


}