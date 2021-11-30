<?php


namespace Kinintel\ValueObjects\Dataset;


use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class EvaluatedDataset {

    /**
     * Dataset instance summary
     *
     * @var DatasetInstanceSummary
     */
    private $dataSetInstanceSummary;

    /**
     * Transformation instances
     *
     * @var TransformationInstance[]
     */
    private $transformationInstances;


    /**
     * Parameter values
     *
     * @var mixed[]
     */
    private $parameterValues;


    /**
     * @var integer
     */
    private $offset;


    /**
     * @var integer
     */
    private $limit;

    /**
     * @return DatasetInstanceSummary
     */
    public function getDataSetInstanceSummary() {
        return $this->dataSetInstanceSummary;
    }

    /**
     * @param DatasetInstanceSummary $dataSetInstanceSummary
     */
    public function setDataSetInstanceSummary($dataSetInstanceSummary) {
        $this->dataSetInstanceSummary = $dataSetInstanceSummary;
    }


    /**
     * @return TransformationInstance[]
     */
    public function getTransformationInstances() {
        return $this->transformationInstances;
    }

    /**
     * @param TransformationInstance[] $transformationInstances
     */
    public function setTransformationInstances($transformationInstances) {
        $this->transformationInstances = $transformationInstances;
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
     * @return int
     */
    public function getOffset() {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset) {
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit) {
        $this->limit = $limit;
    }


}
