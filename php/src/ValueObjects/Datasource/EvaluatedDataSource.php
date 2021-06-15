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


}