<?php

namespace Kinintel\ValueObjects\Util\Analysis\TextAnalysis;

class StopWord {

    /**
     * @var boolean
     */
    private $builtIn;

    /**
     * @var boolean
     */
    private $custom;

    /**
     * @var string
     */
    private $datasourceKey;

    /**
     * @var string
     */
    private $datasourceColumn;

    /**
     * @var integer
     */
    private $includeStopwordsAtLength;

    /**
     * @var mixed
     */
    private $list;

    public function __construct($builtIn = true, $custom = false, $datasourceKey = null, $datasourceColumn = null, $includeStopwordsAtLength = 2, $list = []) {
        $this->builtIn = $builtIn;
        $this->custom = $custom;
        $this->datasourceKey = $datasourceKey;
        $this->datasourceColumn = $datasourceColumn;
        $this->includeStopwordsAtLength = $includeStopwordsAtLength;
        $this->list = $list;
    }

    /**
     * @return bool
     */
    public function isBuiltIn() {
        return $this->builtIn;
    }

    /**
     * @param bool $builtIn
     */
    public function setBuiltIn($builtIn) {
        $this->builtIn = $builtIn;
    }

    /**
     * @return bool
     */
    public function isCustom() {
        return $this->custom;
    }

    /**
     * @param bool $custom
     */
    public function setCustom($custom) {
        $this->custom = $custom;
    }

    /**
     * @return string
     */
    public function getDatasourceKey() {
        return $this->datasourceKey;
    }

    /**
     * @param string $datasourceKey
     */
    public function setDatasourceKey($datasourceKey) {
        $this->datasourceKey = $datasourceKey;
    }

    /**
     * @return string
     */
    public function getDatasourceColumn() {
        return $this->datasourceColumn;
    }

    /**
     * @param string $datasourceColumn
     */
    public function setDatasourceColumn($datasourceColumn) {
        $this->datasourceColumn = $datasourceColumn;
    }

    /**
     * @return int
     */
    public function getMinPhraseLength() {
        return $this->includeStopwordsAtLength;
    }

    /**
     * @param int $minPhraseLength
     */
    public function setMinPhraseLength($minPhraseLength) {
        $this->includeStopwordsAtLength = $minPhraseLength;
    }

    /**
     * @return mixed
     */
    public function getList() {
        return $this->list;
    }

    /**
     * @param mixed $list
     */
    public function setList($list) {
        $this->list = $list;
    }


}
