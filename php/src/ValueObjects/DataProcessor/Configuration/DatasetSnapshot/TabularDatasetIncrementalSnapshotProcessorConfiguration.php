<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot;

use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorAction;
use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorActions;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;

class TabularDatasetIncrementalSnapshotProcessorConfiguration {

    use DataProcessorActions;

    /**
     * Parameter Values for the data set instance if required
     *
     * @var mixed[]
     */
    private $parameterValues;


    /**
     * Key fields which form the primary key for the data set.
     * If not supplied it is assumed that all values form the primary key
     *
     * @var string[]
     */
    private $keyFieldNames = [];


    /**
     * @var Index[]
     */
    private $indexes = [];


    /**
     * The name of the field used for detecting newer values in the data set.
     *
     * @var string
     */
    private $newerValuesFieldName;


    /**
     * Which rule is in use for determining latest items
     *
     * @var string
     */
    private $newerValuesRule = self::LATEST_VALUE_GREATER;


    /**
     * @var int
     */
    private $readChunkSize = null;


    const LATEST_VALUE_GREATER = "GREATER";
    const LATEST_VALUE_GREATER_OR_EQUAL = "GREATER_OR_EQUAL";
    const LATEST_VALUE_LESSER = "LESSER";
    const LATEST_VALUE_LESSER_OR_EQUAL = "LESSER_OR_EQUAL";


    /**
     * Construct
     *
     * @param mixed[] $parameterValues
     * @param string $newerValuesFieldName
     * @param string $newerValuesRule
     * @param string[] $keyFieldNames
     * @param array $indexes
     * @param int $readChunkSize
     */
    public function __construct($parameterValues, $newerValuesFieldName, $newerValuesRule = self::LATEST_VALUE_GREATER, $keyFieldNames = [], $indexes = [], $readChunkSize = null) {
        $this->newerValuesFieldName = $newerValuesFieldName;
        $this->newerValuesRule = $newerValuesRule;
        $this->keyFieldNames = $keyFieldNames;
        $this->readChunkSize = $readChunkSize;
        $this->indexes = $indexes;
        $this->parameterValues = $parameterValues;
    }


    /**
     * @return mixed[]
     */
    public function getParameterValues() {
        return $this->parameterValues;
    }

    /**
     * @param mixed[] $parameterValues
     */
    public function setParameterValues($parameterValues) {
        $this->parameterValues = $parameterValues;
    }


    /**
     * @return string[]
     */
    public function getKeyFieldNames() {
        return $this->keyFieldNames;
    }

    /**
     * @param string[] $keyFieldNames
     */
    public function setKeyFieldNames($keyFieldNames) {
        $this->keyFieldNames = $keyFieldNames;
    }

    /**
     * @return Index[]
     */
    public function getIndexes() {
        return $this->indexes;
    }

    /**
     * @param Index[] $indexes
     */
    public function setIndexes($indexes) {
        $this->indexes = $indexes;
    }


    /**
     * @return string
     */
    public function getNewerValuesFieldName() {
        return $this->newerValuesFieldName;
    }

    /**
     * @param string $newerValuesFieldName
     */
    public function setNewerValuesFieldName($newerValuesFieldName) {
        $this->newerValuesFieldName = $newerValuesFieldName;
    }

    /**
     * @return string
     */
    public function getNewerValuesRule() {
        return $this->newerValuesRule;
    }

    /**
     * @param string $newerValuesRule
     */
    public function setNewerValuesRule($newerValuesRule) {
        $this->newerValuesRule = $newerValuesRule;
    }

    /**
     * @return int
     */
    public function getReadChunkSize() {
        return $this->readChunkSize;
    }

    /**
     * @param int $readChunkSize
     */
    public function setReadChunkSize($readChunkSize) {
        $this->readChunkSize = $readChunkSize;
    }


    /**
     * Return applicable processor actions for this one.
     *
     * @param $dataProcessorInstanceKey
     * @return DataProcessorAction[]
     */
    public function getProcessorActions($dataProcessorInstanceKey) {
        return [
            new DataProcessorAction("Select", $dataProcessorInstanceKey)
        ];
    }
}