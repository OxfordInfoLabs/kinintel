<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Query\Query;

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


    public function applyQuery($query) {
        // TODO: Implement applyQuery() method.
    }

    public function materialise() {
        // TODO: Implement materialise() method.
    }
}