<?php

namespace Kinintel\ValueObjects\Util\TextAnalysis;

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
    private $minPhraseLength;

    /**
     * @var mixed
     */
    private $list;

    public function __construct($builtIn, $custom, $datasourceKey, $datasourceColumn, $minPhraseLength, $list = []) {
        $this->builtIn = $builtIn;
        $this->custom = $custom;
        $this->datasourceKey = $datasourceKey;
        $this->datasourceColumn = $datasourceColumn;
        $this->minPhraseLength = $minPhraseLength;
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
        return $this->minPhraseLength;
    }

    /**
     * @param int $minPhraseLength
     */
    public function setMinPhraseLength($minPhraseLength) {
        $this->minPhraseLength = $minPhraseLength;
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
