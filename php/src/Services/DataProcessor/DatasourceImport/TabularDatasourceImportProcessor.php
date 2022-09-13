<?php


namespace Kinintel\Services\DataProcessor\DatasourceImport;


use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
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
     * DatasourceImportProcessor constructor.
     *
     * @param DatasourceService $datasourceService
     * @param DatasetService $datasetService
     */
    public function __construct($datasourceService, $datasetService) {
        $this->datasourceService = $datasourceService;
        $this->datasetService = $datasetService;
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
     * @param DataProcessorInstance $instance
     */
    public function process($instance) {
        $config = $instance->returnConfig();

        // Read and write chunk size
        $sourceReadChunkSize = $config->getSourceReadChunkSize();
        $targetWriteChunkSize = $config->getTargetWriteChunkSize();

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

            $offset = $sourceReadChunkSize ? 0 : null;
            do {

                $sourceDataset = $this->datasetService->getEvaluatedDataSetForDataSetInstance($config->getSourceDataset(), null, null, $offset, $sourceReadChunkSize);

                $readEntries = $this->populateTargetDatasources($sourceDataset, $targetDatasources, $targetWriteChunkSize);

                if ($sourceReadChunkSize)
                    $offset += $sourceReadChunkSize;

            } while ($readEntries >= ($sourceReadChunkSize ?? PHP_INT_MAX));


        } else {

            $sourceDatasourceKeys = $config->getSourceDatasourceKey() ? [$config->getSourceDatasourceKey()] : $config->getSourceDatasourceKeys();

            // Process all applicable data sources
            foreach ($sourceDatasourceKeys as $sourceDatasourceKey) {

                // Ensure a single placeholder parameter set in place if none supplied
                $sourceParamSets = $config->getSourceParameterSets() ?: [[]];

                // Loop through param sets
                foreach ($sourceParamSets as $paramSet) {

                    $offset = $sourceReadChunkSize ? 0 : null;
                    do {
                        $sourceDataset = $this->datasourceService->getEvaluatedDataSource($sourceDatasourceKey, $paramSet, null, $offset, $sourceReadChunkSize);

                        if ($sourceDataset) {

                            if (!($sourceDataset instanceof TabularDataset)) {
                                throw new UnsupportedDatasetException("Tabular datasets must be returned from source datasources");
                            }

                            $readEntries = $this->populateTargetDatasources($sourceDataset, $targetDatasources, $targetWriteChunkSize);

                            if ($sourceReadChunkSize)
                                $offset += $sourceReadChunkSize;
                        } else {
                            $readEntries = 0;
                        }
                    } while ($readEntries >= ($sourceReadChunkSize ?? PHP_INT_MAX));
                }

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
    private function populateTargetDatasources($sourceDataset, $targetDatasources, $chunkSize) {

        if (!($sourceDataset instanceof TabularDataset)) {
            throw new UnsupportedDatasetException("The source datasource supplied to the processor must materialise to a tabular data set");
        }

        // Ensure fields are in plain format to avoid double
        $fields = Field::toPlainFields($sourceDataset->getColumns());

        // Chunk the results according to chunk size for scalability.
        $read = 0;
        $dataItems = [];
        while (($dataItem = $sourceDataset->nextDataItem()) !== false) {

            $dataItems[] = $dataItem;
            $read++;
            if ($read % $chunkSize == 0) {
                $this->processTargetChunkResults($targetDatasources, $fields, $dataItems);
                $dataItems = [];
            }
        }

        // Process remainder if required
        if (sizeof($dataItems))
            $this->processTargetChunkResults($targetDatasources, $fields, $dataItems);

        return $read;
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