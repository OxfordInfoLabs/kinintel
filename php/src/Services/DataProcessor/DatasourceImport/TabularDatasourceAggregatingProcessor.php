<?php

namespace Kinintel\Services\DataProcessor\DatasourceImport;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\DataProcessor\BaseDataProcessor;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceAggregatingProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceAggregatingSource;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\Sort;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class TabularDatasourceAggregatingProcessor extends BaseDataProcessor {

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

                    if (($value["latest_discover_time"] ?? null) > ($dataset[$key]["latest_discover_time"] ?? null)) {
                        $dataset[$key]["latest_discover_time"] = $value["latest_discover_time"];
                    }

                    // Increment sources with results
                    $dataset[$key]["sources_with_results"]++;
                }
            }

            // Add on the rest
            $dataset = array_merge($dataset, $newData);

        }

        // Reset the keys
        $dataset = array_values($dataset);

        // Fully augment with nulls the first item, so all fields are updated
        $fields = [];
        foreach ($sourceDatasources as $source) {
            $fields = array_merge($fields, array_values($source->getColumnMappings()));
            $fields[] = $source->getSourceIndicatorColumn();
        }


        $first = $dataset[0];
        foreach ($fields as $field) {
            if (!isset($first[$field])) {
                $first[$field] = null;
            }
        }

        $dataset[0] = $first;


        // Update the target datasource
        $update = new DatasourceUpdate([], [], [], $dataset);
        $this->datasourceService->updateDatasourceInstanceByKey($config->getTargetDatasourceKey(), $update, true);
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
        $dataset = $this->datasourceService->getEvaluatedDataSourceByInstanceKey($datasourceInstanceKey, [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort($aggSource->getDateColumn(), "desc")]))
        ]);

        $data = $dataset->getAllData();

        // Construct array and augment
        $latestData = [];
        $keyFields = $config->getKeyFields();
        $columnMappings = $aggSource->getColumnMappings();
        foreach ($data as &$dataItem) {
            $key = "";
            $discoverTime = $dataItem[$aggSource->getDateColumn()] ?? null;

            // Do the field mappings
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
            }


            // Add source flag
            $dataItem[$aggSource->getSourceIndicatorColumn()] = true;

            // Set date and time fields
            $dataItem["window_time"] = $fromDate;
            $dataItem["latest_discover_time"] = $discoverTime;
            $dataItem["discover_month"] = date_create_from_format("Y-m-d H:i:s", $fromDate)->format("F");
            $dataItem["discover_month_index"] = date_create_from_format("Y-m-d H:i:s", $fromDate)->format("n");
            $dataItem["discover_year"] = date_create_from_format("Y-m-d H:i:s", $fromDate)->format("Y");
            $dataItem["sources_with_results"] = 1;

            $latestData[$key] = $dataItem;
        }

        return $latestData;
    }

    public function onInstanceDelete($instance) {

    }

}