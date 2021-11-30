<?php


namespace Kinintel\Test\Services\Dataset\Exporter;


use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;

class TestExporterConfig implements DatasetExporterConfiguration {


    /**
     * @var string
     * @required
     */
    private $param1;


    /**
     * @var int
     * @required
     */
    private $param2;

    /**
     * TestExporterConfig constructor.
     * @param string $param1
     * @param int $param2
     */
    public function __construct($param1 = null, $param2 = null) {
        $this->param1 = $param1;
        $this->param2 = $param2;
    }


    /**
     * @return string
     */
    public function getParam1() {
        return $this->param1;
    }

    /**
     * @param string $param1
     */
    public function setParam1($param1) {
        $this->param1 = $param1;
    }

    /**
     * @return int
     */
    public function getParam2() {
        return $this->param2;
    }

    /**
     * @param int $param2
     */
    public function setParam2($param2) {
        $this->param2 = $param2;
    }


}