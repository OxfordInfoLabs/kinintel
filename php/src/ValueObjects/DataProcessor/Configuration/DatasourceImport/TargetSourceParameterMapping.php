<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;

class TargetSourceParameterMapping {

    /**
     * Name of parameter to be passed to the source datasources / dataset
     *
     * @var string
     */
    private $sourceParameterName;


    /**
     * Index of configured target datasource to read parameter value from
     *
     * @var integer
     */
    private $targetDatasourceIndex;


    /**
     * Name of field on target datasource to use to read parameter value
     *
     * @var string
     */
    private $targetDatasourceField;


    /**
     * Which rule to use to read the parameter value (usually latest)
     *
     * @var string
     */
    private $targetValueRule = self::VALUE_RULE_LATEST;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * An associative array of values keyed by field name which will be applied when querying for the target value
     * to the target datasource.
     *
     * @var array
     */
    private $additionalTargetDatasourceFilters;


    // Value rules
    const VALUE_RULE_LATEST = "latest";
    const VALUE_RULE_EARLIEST = "earliest";

    /**
     * @param string $sourceParameterName
     * @param int $targetDatasourceIndex
     * @param string $targetDatasourceField
     * @param string $targetValueRule
     */
    public function __construct($sourceParameterName, $targetDatasourceIndex, $targetDatasourceField, $targetValueRule = self::VALUE_RULE_LATEST, $defaultValue = null, $additionalTargetDatasourceFilters = []) {
        $this->sourceParameterName = $sourceParameterName;
        $this->targetDatasourceIndex = $targetDatasourceIndex;
        $this->targetDatasourceField = $targetDatasourceField;
        $this->targetValueRule = $targetValueRule;
        $this->defaultValue = $defaultValue;
        $this->additionalTargetDatasourceFilters = $additionalTargetDatasourceFilters;
    }


    /**
     * @return string
     */
    public function getSourceParameterName() {
        return $this->sourceParameterName;
    }

    /**
     * @param string $sourceParameterName
     */
    public function setSourceParameterName($sourceParameterName) {
        $this->sourceParameterName = $sourceParameterName;
    }

    /**
     * @return int
     */
    public function getTargetDatasourceIndex() {
        return $this->targetDatasourceIndex;
    }

    /**
     * @param int $targetDatasourceIndex
     */
    public function setTargetDatasourceIndex($targetDatasourceIndex) {
        $this->targetDatasourceIndex = $targetDatasourceIndex;
    }

    /**
     * @return string
     */
    public function getTargetDatasourceField() {
        return $this->targetDatasourceField;
    }

    /**
     * @param string $targetDatasourceField
     */
    public function setTargetDatasourceField($targetDatasourceField) {
        $this->targetDatasourceField = $targetDatasourceField;
    }

    /**
     * @return string
     */
    public function getTargetValueRule() {
        return $this->targetValueRule;
    }

    /**
     * @param string $targetValueRule
     */
    public function setTargetValueRule($targetValueRule) {
        $this->targetValueRule = $targetValueRule;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue() {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue) {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return array
     */
    public function getAdditionalTargetDatasourceFilters() {
        return $this->additionalTargetDatasourceFilters;
    }

    /**
     * @param array $additionalTargetDatasourceFilters
     */
    public function setAdditionalTargetDatasourceFilters($additionalTargetDatasourceFilters) {
        $this->additionalTargetDatasourceFilters = $additionalTargetDatasourceFilters;
    }


}