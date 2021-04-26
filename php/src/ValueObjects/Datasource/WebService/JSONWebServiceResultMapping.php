<?php


namespace Kinintel\ValueObjects\Datasource\WebService;

/**
 * Result mapping rules for JSON web service calls
 *
 * Class JSONWebServiceResultMapping
 * @package Kinintel\ValueObjects\Datasource\WebService
 */
class JSONWebServiceResultMapping {

    /**
     * Path to the result data from top of JSON tree - defaults to blank (top of tree)
     *
     * @var string
     */
    private $resultPath;


    /**
     * If set the result will be assumed to be a single result and not an array and will be
     * interpreted accordingly
     *
     * @var bool
     */
    private $singleResult;

    /**
     * JSONWebServiceResultMapping constructor.
     *
     * @param string $resultPath
     * @param bool $singleResult
     */
    public function __construct($resultPath = "", $singleResult = false) {
        $this->resultPath = $resultPath;
        $this->singleResult = $singleResult;
    }


    /**
     * @return string
     */
    public function getResultPath() {
        return $this->resultPath;
    }

    /**
     * @param string $resultPath
     */
    public function setResultPath($resultPath) {
        $this->resultPath = $resultPath;
    }

    /**
     * @return bool
     */
    public function isSingleResult() {
        return $this->singleResult;
    }

    /**
     * @param bool $singleResult
     */
    public function setSingleResult($singleResult) {
        $this->singleResult = $singleResult;
    }


}