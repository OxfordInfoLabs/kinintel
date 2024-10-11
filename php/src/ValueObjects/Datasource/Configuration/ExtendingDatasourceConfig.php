<?php

namespace Kinintel\ValueObjects\Datasource\Configuration;

use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * Configuration for the Extending Datasource.
 */
class ExtendingDatasourceConfig implements DatasourceConfig {

    /**
     * @var string
     * @required
     */
    private $baseDatasourceKey;

    /**
     * @var TransformationInstance[]
     */
    private $transformationInstances;

    /**
     * @param string $baseDatasourceKey
     * @param TransformationInstance[] $transformationInstances
     */
    public function __construct($baseDatasourceKey, $transformationInstances = []) {
        $this->baseDatasourceKey = $baseDatasourceKey;
        $this->transformationInstances = $transformationInstances;
    }


    /**
     * @return string
     */
    public function getBaseDatasourceKey() {
        return $this->baseDatasourceKey;
    }

    /**
     * @param string $baseDatasourceKey
     */
    public function setBaseDatasourceKey($baseDatasourceKey) {
        $this->baseDatasourceKey = $baseDatasourceKey;
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


}