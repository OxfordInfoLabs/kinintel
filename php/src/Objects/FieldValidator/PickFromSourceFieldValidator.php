<?php

namespace Kinintel\Objects\FieldValidator;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ArrayUtils;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;

class PickFromSourceFieldValidator implements FieldValidator {

    // Static allowed values by source collection for efficiency
    public static $allowedValuesBySource = [];

    /**
     * @var DatasetService
     */
    private DatasetService $datasetService;

    /**
     * @var DatasourceService
     */
    private DatasourceService $datasourceService;

    /**
     * Construct with parameters for pick from
     *
     * @param string $valueFieldName
     * @param int|null $datasetId
     * @param string|null $datasourceInstanceKey
     */
    public function __construct(private string $valueFieldName, private ?int $datasetId = null, private ?string $datasourceInstanceKey = null) {
        $this->datasetService = Container::instance()->get(DatasetService::class);
        $this->datasourceService = Container::instance()->get(DatasourceService::class);
    }


    /**
     * Validate a value based upon pick list.
     *
     * @param $value
     * @param $field
     * @return bool|string
     */
    public function validateValue($value, $field) {

        // Allow blanks
        if ($value === null)
            return true;

        $allowedValues = $this->lookupValues();

        if (in_array($value, $allowedValues, is_bool($value)))
            return true;
        else
            return "Invalid value supplied for " . $field->getName();

    }


    /**
     * Testing setter
     *
     * @param DatasetService $datasetService
     */
    public function setDatasetService(DatasetService $datasetService): void {
        $this->datasetService = $datasetService;
    }

    /**
     * Testing setter
     *
     * @param DatasourceService $datasourceService
     */
    public function setDatasourceService(DatasourceService $datasourceService): void {
        $this->datasourceService = $datasourceService;
    }


    /**
     * @return string
     */
    public function getValueFieldName(): string {
        return $this->valueFieldName;
    }

    /**
     * @param string $valueFieldName
     */
    public function setValueFieldName(string $valueFieldName): void {
        $this->valueFieldName = $valueFieldName;
    }

    /**
     * @return int|null
     */
    public function getDatasetId(): ?int {
        return $this->datasetId;
    }

    /**
     * @param int|null $datasetId
     */
    public function setDatasetId(?int $datasetId): void {
        $this->datasetId = $datasetId;
    }

    /**
     * @return string|null
     */
    public function getDatasourceInstanceKey(): ?string {
        return $this->datasourceInstanceKey;
    }

    /**
     * @param string|null $datasourceInstanceKey
     */
    public function setDatasourceInstanceKey(?string $datasourceInstanceKey): void {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
    }

    // Lookup values
    private function lookupValues() {
        $key = $this->datasourceInstanceKey ?? $this->datasetId;
        if (!isset(self::$allowedValuesBySource[$key])) {

            $resultSet = null;
            if ($this->datasourceInstanceKey) {
                $resultSet = $this->datasourceService->getEvaluatedDataSourceByInstanceKey($this->datasourceInstanceKey, [], [], 0, PHP_INT_MAX);
            } else if ($this->datasetId) {
                $resultSet = $this->datasetService->getEvaluatedDataSetForDataSetInstanceById($this->datasetId, [], [], 0, PHP_INT_MAX);
            }

            if ($resultSet) {
                $values = [];
                foreach ($resultSet->getAllData() as $datum) {
                    if ($datum[$this->valueFieldName] ?? null)
                        $values[$datum[$this->valueFieldName]] = 1;
                }
                self::$allowedValuesBySource[$key] = array_keys($values);
            } else {
                self::$allowedValuesBySource[$key] = [];
            }

        }

        return self::$allowedValuesBySource[$key] ?? [];
    }


}