<?php


namespace Kinintel\Objects\Datasource;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\ValueFunction\ValueFunctionEvaluator;
use Kinikit\Core\Logging\Logger;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\UpdatableMappedField;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;

abstract class BaseUpdatableDatasource extends BaseDatasource implements UpdatableDatasource {

    /**
     * @var DatasourceUpdateConfig
     */
    private $updateConfig;

    private DatasourceService $datasourceService;
    private ValueFunctionEvaluator $valueFunctionEvaluator;

    /**
     * BaseUpdatableDatasource constructor.
     * @param DatasourceUpdateConfig $updateConfig
     */
    public function __construct($config = null, $authenticationCredentials = null, $updateConfig = null, $validator = null, $instanceKey = null, $instanceTitle = null) {
        parent::__construct($config, $authenticationCredentials, $validator, $instanceKey, $instanceTitle);
        $this->updateConfig = $updateConfig;
        $this->datasourceService = Container::instance()->get(DatasourceService::class);
        $this->valueFunctionEvaluator = Container::instance()->get(ValueFunctionEvaluator::class);
    }

    /**
     * @param DatasourceService $datasourceService
     */
    public function setDatasourceService($datasourceService) {
        $this->datasourceService = $datasourceService;
    }

    /**
     * @param ValueFunctionEvaluator $valueFunctionEvaluator
     */
    public function setValueFunctionEvaluator($valueFunctionEvaluator) {
        $this->valueFunctionEvaluator = $valueFunctionEvaluator;
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

            $mappedColumns = [];
            $mappedKeys = [];

            $filteredData = [];
            foreach ($data as $index => $dataItem) {
                // If we have parent filters ensure they are satisfied.
                $matched = true;
                if (sizeof($mappedField->getParentFilters())) {
                    foreach ($mappedField->getParentFilters() as $filterField => $filterValue) {
                        if (!is_array($filterValue)) $filterValue = [$filterValue];
                        if (!in_array($dataItem[$filterField] ?? null, $filterValue)) {
                            $matched = false;
                            break;
                        }
                    }
                }
                if ($matched) $filteredData[$index] = $dataItem;
            }


            foreach ($filteredData as $dataItem) {
                // When replacing, we need to know the key fields which we are going to replace over
                // The parent fields are used for this, e.g.
                // {a: 1, b: [2, 3]} with parentFieldMappings ["a": "a"] will delete all entries in the child
                // table where a = 1, because "a" is a key field
                $mappedKeyValues = [];
                foreach ($mappedField->getParentFieldMappings() ?? [] as $parentFieldMapping => $childFieldMapping) {
                    $mappedKeyValues[$childFieldMapping] = self::evaluateParentFieldMapping(
                        $this->valueFunctionEvaluator, $parentFieldMapping, $dataItem
                    );
                }
                // We count the constant values as part of the primary key when replacing
                foreach ($mappedField->getConstantFieldValues() ?? [] as $column => $value){
                    $mappedKeyValues[$column] = $value;
                }
                $mappedKeys[] = $mappedKeyValues;
            }

            $mappedData = self::getMappedData($filteredData, $mappedField, $this->valueFunctionEvaluator);


            if (sizeof($mappedData)) {
                $mappedColumns = array_map(
                    fn($item) => new Field($item),
                    array_keys($mappedData[0])
                );
            }


            $updateRule = $mappedField->getUpdateMode() ?? $parentUpdateRule;

            // If a replace operation, delete old items.
            if ($updateRule == self::UPDATE_MODE_REPLACE && !empty($mappedKeys)) {

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

            }


            // Update mapped data source
            $datasource->update(new ArrayTabularDataset($mappedColumns, $mappedData), $updateRule);

            // Remove column from array
            foreach ($columns as $index => $column) {
                if (!$mappedField->isRetainTargetFieldInParent() && ($column->getName() == $mappedField->getFieldName())) {
                    array_splice($columns, $index, 1);
                    break;
                }
            }


        }

        // Return data
        return new ArrayTabularDataset($columns, $data);


    }


    // Evaluate a parent field mapping
    private static function evaluateParentFieldMapping(
        ValueFunctionEvaluator $valueFunctionEvaluator,
        string $parentFieldMapping,
        array $dataItem
    ) {

        // Ensure we wrap strings for value function evaluation.
        $wrappedParentFieldMapping = str_starts_with($parentFieldMapping, "[[")
            ? $parentFieldMapping
            : "[[" . $parentFieldMapping . "]]";

        return $valueFunctionEvaluator->evaluateString($wrappedParentFieldMapping, $dataItem, ["[[", "]]"]);
    }

    public static function getMappedData(
        array $data,
        UpdatableMappedField $mappedField,
        ValueFunctionEvaluator $valueFunctionEvaluator
    ) : array {
        $mappedData = [];

        foreach ($data as $dataItem) {

            if (!isset($dataItem[$mappedField->getFieldName()])) continue;

            // Get and ensure data items are an array
            $mappedDataItems = $dataItem[$mappedField->getFieldName()] ?? [];

            // If we just have a string, we want to wrap it in an array, so we can pass it to mapped fields
            if (!is_array($mappedDataItems)) {
                $mappedDataItems = [$mappedDataItems];
            }

            foreach ($mappedDataItems as $elementIndex => $mappedDataItem) {
                if ($mappedDataItem === null) continue;

                // If a target field name, remap it.
                if ($mappedField->getTargetFieldName()) {
                    $mappedDataItem = [$mappedField->getTargetFieldName() => $mappedDataItem];
                }

                foreach ($mappedField->getParentFieldMappings() ?? [] as $parentFieldMapping => $childFieldMapping) {
                    if (!is_array($dataItem) || !is_array($mappedDataItem)) {
                        Logger::log("Parent field mapping $parentFieldMapping, Child field mapping $childFieldMapping");
                        Logger::log($dataItem);
                        Logger::log("\n\nMappedDataItem:");
                        Logger::log($mappedDataItem);
                        throw new DatasourceUpdateException("Tried to access a non-array in a mapped fields. See logs.");
                    }
                    $mappedDataItem[$childFieldMapping] = $parentFieldMapping === "_index"
                        ? $elementIndex
                        : self::evaluateParentFieldMapping($valueFunctionEvaluator, $parentFieldMapping, $dataItem);
                }

                // Take in a data item and expand its fields, returning an array of items
                // e.g.
                // {name:x, tags:[a,b]}, tags, tag => [{name:x, tag:a}, {name:x,tag:b}]
                $mapChildFields = function (array $dataItem, string $many, string $one): array {
                    $out = [];
                    if (isset($dataItem[$many]) && is_array($dataItem[$many]) && $dataItem[$many]) {
                        $baseItem = $dataItem; // Make a copy
                        unset($baseItem[$many]); // without "tags"
                        foreach ($dataItem[$many] as $value) {
                            $finalItem = $baseItem; // Make a copy of base
                            $finalItem[$one] = $value; // and add the value
                            $out[] = $finalItem;
                        }
                    } else {
                        // Data has an invalid entry in the $many field
                        $dataItem[$one] = null;
                        unset($dataItem[$many]);
                        $out[] = $dataItem; // so set the $one field to null
                    }
                    return $out;
                };

                // Add constant field values
                $mappedDataItem = array_merge($mappedDataItem, $mappedField->getConstantFieldValues() ?? []);

                $mappedDataItems = [$mappedDataItem];

                // If we have fields to flatten
                foreach ($mappedField->getFlattenFieldMappings() as $many => $one) {
                    $newMappedDataItems = [];
                    foreach ($mappedDataItems as $item) {
                        $flattened = $mapChildFields($item, $many, $one);
                        $newMappedDataItems = array_merge($newMappedDataItems, $flattened);
                    }
                    $mappedDataItems = $newMappedDataItems;
                }
                $mappedData = array_merge($mappedData, $mappedDataItems);
            }
        }
        return $mappedData;
    }


}
