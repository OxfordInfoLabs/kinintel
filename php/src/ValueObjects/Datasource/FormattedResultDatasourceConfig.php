<?php


namespace Kinintel\ValueObjects\Datasource;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\ResultFormatter\ResultFormatter;


/**
 * Datasource config where formatted results are expected to provide
 * common configuration for the appropriate formatter.
 *
 * Class FormattedDatasourceConfig
 * @package Kinintel\ValueObjects\Datasource
 */
class FormattedResultDatasourceConfig implements DatasourceConfig {

    /**
     * The format of results expected
     *
     * @var string
     */
    private $resultFormat;

    /**
     * Configuration for the result format
     *
     * @var mixed
     */
    private $resultFormatConfig;

    /**
     * FormattedResultDatasourceConfig constructor.
     *
     * @param string $resultFormat
     * @param mixed $resultFormatConfig
     */
    public function __construct($resultFormat, $resultFormatConfig) {
        $this->resultFormat = $resultFormat;
        $this->resultFormatConfig = $resultFormatConfig;
    }


    /**
     * @return string
     */
    public function getResultFormat() {
        return $this->resultFormat;
    }

    /**
     * @param string $resultFormat
     */
    public function setResultFormat($resultFormat) {
        $this->resultFormat = $resultFormat;
    }

    /**
     * @return mixed
     */
    public function getResultFormatConfig() {
        return $this->resultFormatConfig;
    }

    /**
     * @param mixed $resultFormatConfig
     */
    public function setResultFormatConfig($resultFormatConfig) {
        $this->resultFormatConfig = $resultFormatConfig;
    }

    /**
     * Return the formatter - ensure we convert arrays to objects
     *
     * @return ResultFormatter
     */
    public function returnFormatter() {
        if ($this->resultFormatConfig instanceof ResultFormatter) {
            return $this->resultFormatConfig;
        } else if (is_array($this->resultFormatConfig)) {

            /**
             * @var ObjectBinder $objectBinder
             */
            $objectBinder = Container::instance()->get(ObjectBinder::class);

            $formatterClass = Container::instance()->getInterfaceImplementationClass(ResultFormatter::class, $this->resultFormat);
            return $objectBinder->bindFromArray($this->resultFormatConfig, $formatterClass);
        }
    }


}