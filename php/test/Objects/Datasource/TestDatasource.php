<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Query\Transformation;

class TestDatasource extends BaseDatasource {

    /**
     * @var string
     */
    private $configClass;

    /**
     * @var string[]
     */
    private $supportedCredentialClasses;

    /**
     * @var bool
     */
    private $authenticationRequired;
    /**
     * @var array
     */
    private $supportedTransformationClasses;


    public function __construct($configClass = null, $supportedCredentialClasses = [], $authenticationRequired = true, $supportedTransformationClasses = []) {
        $this->configClass = $configClass;
        $this->supportedCredentialClasses = $supportedCredentialClasses;
        $this->authenticationRequired = $authenticationRequired;
        parent::__construct();
        $this->supportedTransformationClasses = $supportedTransformationClasses;
    }

    public function getConfigClass() {
        return $this->configClass;
    }

    public function getSupportedCredentialClasses() {
        return $this->supportedCredentialClasses;
    }

    public function isAuthenticationRequired() {
        return $this->authenticationRequired;
    }

    /**
     * @return array
     */
    public function getSupportedTransformationClasses() {
        return $this->supportedTransformationClasses;
    }


    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {
        // TODO: Implement applyQuery() method.
    }

    public function materialiseDataset($parameterValues = []) {
        // TODO: Implement materialise() method.
    }
}