<?php

namespace Kinintel\Services\DataProcessor\DatasourceImport;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceAggregatingProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceAggregatingSource;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\Sort;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class TabularDatasourceAggregatingProcessor implements DataProcessor {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;
    }

    /**
     * Return the configuration class for the import processor
     *
     * @return string
     */
    public function getConfigClass() {
        return TabularDatasourceAggregatingProcessorConfiguration::class;
    }

    /**
     * Process a datasource import
     *
     * @param DataProcessorInstance $instance
     */
    public function process($instance) {


        // Import the latest data (the current day) from each datasource

        // Iterate through it all, populating a temp array with source labelled
        // Keyed by key fields values
        // Also have a field with current date/day/month/year

        // When the key fields match, combine the entries

        // Update the combined datasource

        /**
         * @var TabularDatasourceAggregatingProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        // Read and write chunk size
        $sourceReadChunkSize = $config->getSourceReadChunkSize();
        $targetWriteChunkSize = $config->getTargetWriteChunkSize();

        switch ($config->getFrequency()) {
            case TabularDatasourceAggregatingProcessorConfiguration::FREQUENCY_HOURLY:
                $fromDate = (new \DateTime())->format("Y-m-d H:00:00");
                break;
            case TabularDatasourceAggregatingProcessorConfiguration::FREQUENCY_DAILY:
                $fromDate = (new \DateTime("midnight"))->format("Y-m-d H:i:s");
                break;
            case TabularDatasourceAggregatingProcessorConfiguration::FREQUENCY_WEEKLY:
                $fromDate = (new \DateTime("last Monday midnight"))->format("Y-m-d H:i:s");
                break;
        }


        $sourceDatasources = $config->getSourceDatasources();

        // Get the first datasource
        $dataset = $this->getLatestData($sourceDatasources[0], $fromDate, $config);

        // Loop through the other datasources
        for ($i = 1; $i < sizeof($sourceDatasources); $i++) {

            $newData = $this->getLatestData($sourceDatasources[$i], $fromDate, $config);

            // Loop through the items, augmenting the data.
            foreach ($newData as $key => $value) {
                if (isset($dataset[$key])) {
                    $dataset[$key] = $dataset[$key] + $value; // Array union
                    unset($newData[$key]);
                }
            }

            // Add on the rest
            $dataset = array_merge($dataset, $newData);

        }

        // Reset the keys
        $dataset = array_values($dataset);


        // Update the target datasource
        $update = new DatasourceUpdate([], [], [], $dataset);
        $this->datasourceService->updateDatasourceInstance($config->getTargetDatasourceKey(), $update, true);
    }

    /**
     * @param TabularDatasourceAggregatingSource $aggSource
     * @param string $fromDate
     * @param TabularDatasourceAggregatingProcessorConfiguration $config
     * @return array
     */
    private function getLatestData($aggSource, $fromDate, $config) {

        // Get the latest raw data
        $datasourceInstanceKey = $aggSource->getKey();

        $filters = [new Filter("[[{$aggSource->getDateColumn()}]]", $fromDate, "gte")];

        /**
         * @var ArrayTabularDataset $dataset
         */
        $dataset = $this->datasourceService->getEvaluatedDataSource($datasourceInstanceKey, [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort($aggSource->getDateColumn(), "desc")]))
        ]);

        $data = $dataset->getAllData();

        // Construct array and augment
        $latestData = [];
        $keyFields = $config->getKeyFields();
        $columnMappings = $aggSource->getColumnMappings();
        $today = (new \DateTime())->format("Y-m-d H:i:00");
        foreach ($data as &$dataItem) {
            $key = "";

            // Do the mappings
            foreach ($dataItem as $field => $item) {

                // Map across the fields
                unset($dataItem[$field]);
                if (isset($columnMappings[$field])) {
                    $field = $columnMappings[$field];
                    $dataItem[$field] = $item;
                }

                if (in_array($field, $keyFields)) {
                    $key .= $item . "|";
                }

                // Add source flag
                $dataItem[$aggSource->getSourceIndicatorColumn()] = true;
                $dataItem["date_imported"] = $today;

            }

            $latestData[$key] = $dataItem;
        }

        return $latestData;
    }
}