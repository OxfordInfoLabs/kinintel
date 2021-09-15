<?php


namespace Kinintel\ValueObjects\Datasource;

use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * Used for transporting data as a single POST payload for a data source
 *
 * Class EvaluatedDataSource
 * @package Kinintel\ValueObjects\Datasource
 */
class EvaluatedDataSource {

    /**
     * Datasource key
     *
     * @var string
     */
    private $key;


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
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
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