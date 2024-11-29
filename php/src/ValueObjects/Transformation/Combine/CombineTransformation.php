<?php

namespace Kinintel\ValueObjects\Transformation\Combine;

use Kinintel\Objects\Datasource\Datasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class CombineTransformation implements Transformation, SQLDatabaseTransformation {

    /**
     * The key of a datasource to combine with.
     *
     * @var string
     * @requiredEither combinedDataSetInstanceId
     */
    private $combinedDataSourceInstanceKey;


    /**
     * The id of a dataset to combine with, if not
     * combining with a datasource.
     *
     * @var integer
     */
    private $combinedDataSetInstanceId;

    /**
     * Array of source dataset fields indexed by combined field keys.
     *
     * @var Field[string]
     */
    private $fieldKeyMappings;


    /**
     * @var mixed[]
     */
    private $parameterValues;

    /**
     * The type of combine - union/union all/intersect/except.
     *
     * @var string
     */
    private $combineType;

    /**
     * @var Datasource
     */
    private $evaluatedDataSource;


    const COMBINE_TYPE_UNION = "union";
    const COMBINE_TYPE_UNION_ALL = "unionall";
    const COMBINE_TYPE_INTERSECT = "intersect";
    const COMBINE_TYPE_EXCEPT = "except";

    /**
     * @param string $combinedDataSourceInstanceKey
     * @param int $combinedDataSetInstanceId
     * @param string $combineType
     * @param Field[string] $fieldMappings
     * @param mixed[] $parameterValues
     */
    public function __construct($combinedDataSourceInstanceKey = null, $combinedDataSetInstanceId = null, $combineType = self::COMBINE_TYPE_UNION, $fieldMappings = [], $parameterValues = []) {
        $this->combinedDataSourceInstanceKey = $combinedDataSourceInstanceKey;
        $this->combinedDataSetInstanceId = $combinedDataSetInstanceId;
        $this->combineType = $combineType;
        $this->fieldKeyMappings = $fieldMappings;
        $this->parameterValues = $parameterValues;
    }

    /**
     * @return string
     */
    public function getCombinedDataSourceInstanceKey() {
        return $this->combinedDataSourceInstanceKey;
    }

    /**
     * @param string $combinedDataSourceInstanceKey
     */
    public function setCombinedDataSourceInstanceKey($combinedDataSourceInstanceKey) {
        $this->combinedDataSourceInstanceKey = $combinedDataSourceInstanceKey;
    }

    /**
     * @return int
     */
    public function getCombinedDataSetInstanceId() {
        return $this->combinedDataSetInstanceId;
    }

    /**
     * @param int $combinedDataSetInstanceId
     */
    public function setCombinedDataSetInstanceId($combinedDataSetInstanceId) {
        $this->combinedDataSetInstanceId = $combinedDataSetInstanceId;
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
     * @return string
     */
    public function getCombineType() {
        return $this->combineType;
    }

    /**
     * @param string $combineType
     */
    public function setCombineType($combineType) {
        $this->combineType = $combineType;
    }

    /**
     * @return Datasource
     */
    public function returnEvaluatedDataSource() {
        return $this->evaluatedDataSource;
    }

    /**
     * @param Datasource $evaluatedDataSource
     */
    public function setEvaluatedDataSource($evaluatedDataSource) {
        $this->evaluatedDataSource = $evaluatedDataSource;
    }

    /**
     * @return Field[string]
     */
    public function getFieldKeyMappings() {
        return $this->fieldKeyMappings;
    }

    /**
     * @param Field[string] $fieldKeyMappings
     */
    public function setFieldKeyMappings($fieldKeyMappings) {
        $this->fieldKeyMappings = $fieldKeyMappings;
    }

    /**
     * @return string
     */
    public function getSQLTransformationProcessorKey() {
        return "combine";
    }

    public function returnAlteredColumns(array $columns): array {
        return $columns;
    }
}