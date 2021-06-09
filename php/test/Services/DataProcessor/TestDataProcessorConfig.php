<?php


namespace Kinintel\Test\Services\DataProcessor;


class TestDataProcessorConfig {

    /**
     * @required
     * @var mixed
     */
    private $property;

    /**
     * TestDataProcessorConfig constructor.
     * @param $property
     */
    public function __construct($property = null) {
        $this->property = $property;
    }


    /**
     * @return mixed
     */
    public function getProperty() {
        return $this->property;
    }

    /**
     * @param mixed $property
     */
    public function setProperty($property) {
        $this->property = $property;
    }


}