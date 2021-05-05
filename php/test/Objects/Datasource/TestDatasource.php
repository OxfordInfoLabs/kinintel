<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Query\Transformation;

class TestDatasource extends Datasource {

    /**
     * @var string
     */
    private $configClass;

    /**
     * @var string[]
     */
    private $supportedCredentialClasses;


    public function __construct($configClass = null, $supportedCredentialClasses = []) {
        $this->configClass = $configClass;
        $this->supportedCredentialClasses = $supportedCredentialClasses;
        parent::__construct();
    }

    public function getConfigClass() {
        return $this->configClass;
    }

    public function getSupportedCredentialClasses() {
        return $this->supportedCredentialClasses;
    }


    public function applyTransformation($transformation) {
        // TODO: Implement applyQuery() method.
    }

    public function materialise() {
        // TODO: Implement materialise() method.
    }
}