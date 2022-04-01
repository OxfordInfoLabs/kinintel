<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\ValueObjects\Datasource\Configuration\DatasourceConfig;

class TestDatasourceConfig implements DatasourceConfig {

    /**
     * @var string
     * @required
     */
    private $property;

    /**
     * TestDatasourceConfig constructor.
     * @param string $property
     */
    public function __construct($property = null) {
        $this->property = $property;
    }


    /**
     * @return string
     */
    public function getProperty() {
        return $this->property;
    }

    /**
     * @param string $property
     */
    public function setProperty($property) {
        $this->property = $property;
    }
}