<?php


namespace Kinintel\Objects\Datasource;


use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;

abstract class BaseUpdatableDatasource extends BaseDatasource implements UpdatableTabularDatasource {

    /**
     * @var DatasourceUpdateConfig
     */
    private $updateConfig;


    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * BaseUpdatableDatasource constructor.
     * @param DatasourceUpdateConfig $updateConfig
     */
    public function __construct($config = null, $authenticationCredentials = null, $updateConfig = null, $validator = null, $instanceKey = null, $instanceTitle = null) {
        parent::__construct($config, $authenticationCredentials, $validator, $instanceKey, $instanceTitle);
        $this->updateConfig = $updateConfig;
        $this->datasourceService = Container::instance()->get(DatasourceService::class);

    }

    /**
     * @param DatasourceService $datasourceService
     */
    public function setDatasourceService($datasourceService) {
        $this->datasourceService = $datasourceService;
    }


    /**
     * Default to returning the standard datasource update config
     *
     * @return string
     */
    public function getUpdateConfigClass() {
        return DatasourceUpdateConfig::class;
    }


    /**
     * @return DatasourceUpdateConfig
     */
    public function getUpdateConfig() {
        return $this->updateConfig;
    }

    /**
     * @param DatasourceUpdateConfig $updateConfig
     */
    public function setUpdateConfig($updateConfig) {
        $this->updateConfig = $updateConfig;
    }


    /**
     * Update mapped field data for the dataset and return a new dataset with the column data pruned
     *
     * @param Dataset $dataSet
     * @return Dataset
     */
    public function updateMappedFieldData($dataSet, $parentUpdateRule = self::UPDATE_MODE_ADD) {

        $mappedFields = $this->getUpdateConfig()->getMappedFields();

        // If no mapped fields, return immediately
        if (!$mappedFields || sizeof($mappedFields) == 0) {
            return $dataSet;
        }

        $data = $dataSet->getAllData();
        $columns = $dataSet->getColumns();

        // Loop through all mapped fields and add data accordingly
        foreach ($mappedFields as $mappedField) {

            // Grab the datasource instance and therefore the data source
            $datasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($mappedField->getDatasourceInstanceKey());
            $datasource = $datasourceInstance->returnDataSource();

            $mappedData = [];
            $mappedColumns = [];
            $mappedKeys = [];
            foreach ($data as $index => $dataItem) {

                $mappedKeyValues = [];
                foreach ($mappedField->getParentFieldMappings() ?? [] as $parentFieldMapping => $childFieldMapping) {
                    $mappedKeyValues[$childFieldMapping] = $dataItem[$parentFieldMapping] ?? null;
                }
                $mappedKeys[] = $mappedKeyValues;

                // Get and ensure data items are an array
                $mappedDataItems = $dataItem[$mappedField->getFieldName()] ?? [];
                if (!is_array($mappedDataItems)) $mappedDataItems = [$mappedDataItems];

                foreach ($mappedDataItems as $mappedDataItem) {

                    foreach ($mappedField->getParentFieldMappings() ?? [] as $parentFieldMapping => $childFieldMapping) {
                        $mappedDataItem[$childFieldMapping] = $dataItem[$parentFieldMapping] ?? null;

                    }
                    $mappedData[] = $mappedDataItem;
                }


                if (sizeof($mappedData)) {
                    $mappedColumns = array_map(function ($item) {
                        return new Field($item);
                    }, array_keys($mappedData[0]));
                }

                // Update core data
                unset($data[$mappedField->getFieldName()]);
                $data[$index] = $dataItem;

            }

            $updateRule = $mappedField->getUpdateMode() ?? $parentUpdateRule;

            // If a replace operation, delete old items.
            if ($parentUpdateRule == self::UPDATE_MODE_REPLACE) {

                $filterJunctions = [];
                foreach ($mappedKeys as $keySet) {
                    $filters = [];
                    foreach ($keySet as $key => $value) {
                        $filters[] = new Filter("[[" . $key . "]]", $value, Filter::FILTER_TYPE_EQUALS);
                    }
                    $filterJunctions[] = new FilterJunction($filters, [], FilterJunction::LOGIC_AND);
                }

                // Create a filter transformation to apply to the datasource
                $transformation = new FilterTransformation([],
                    $filterJunctions,
                    FilterJunction::LOGIC_OR
                );

                $filteredSource = $datasource->applyTransformation($transformation);
                $existingItems = $filteredSource->materialise();

                // Delete previous items
                $datasource->update($existingItems, UpdatableDatasource::UPDATE_MODE_DELETE);

                // Change update rule to an add
                $updateRule = BaseUpdatableDatasource::UPDATE_MODE_ADD;

            }


            // Update mapped data source
            $datasource->update(new ArrayTabularDataset($mappedColumns, $mappedData), $updateRule);

            // Remove column from array
            foreach ($columns as $index => $column) {
                if ($column->getName() == $mappedField->getFieldName()) {
                    array_splice($columns, $index, 1);
                    break;
                }
            }


        }

        // Return data
        return new ArrayTabularDataset($columns, $data);


    }


}