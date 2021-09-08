<?php


namespace Kinintel\Test\Objects\Alert;


use Kinintel\ValueObjects\Alert\MatchRule\AlertMatchRuleConfiguration;

class TestAlertMatchRuleConfiguration implements AlertMatchRuleConfiguration {

    private $property1;
    private $property2;

    /**
     * TestAlertMatchRuleConfiguration constructor.
     * @param $property1
     * @param $property2
     */
    public function __construct($property1, $property2) {
        $this->property1 = $property1;
        $this->property2 = $property2;
    }


    /**
     * @return mixed
     */
    public function getProperty1() {
        return $this->property1;
    }

    /**
     * @param mixed $property1
     */
    public function setProperty1($property1) {
        $this->property1 = $property1;
    }

    /**
     * @return mixed
     */
    public function getProperty2() {
        return $this->property2;
    }

    /**
     * @param mixed $property2
     */
    public function setProperty2($property2) {
        $this->property2 = $property2;
    }


}