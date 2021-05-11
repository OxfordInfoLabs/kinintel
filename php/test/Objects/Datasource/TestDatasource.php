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


    public function __construct($configClass = null, $supportedCredentialClasses = [], $authenticationRequired = true) {
        $this->configClass = $configClass;
        $this->supportedCredentialClasses = $supportedCredentialClasses;
        $this->authenticationRequired = $authenticationRequired;
        parent::__construct();
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


    public function applyTransformation($transformation) {
        // TODO: Implement applyQuery() method.
    }

    public function materialiseDataset() {
        // TODO: Implement materialise() method.
    }
}