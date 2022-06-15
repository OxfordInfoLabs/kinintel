<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\Document;

use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;

class DocumentDatasourceConfig extends SQLDatabaseDatasourceConfig {

    /**
     * @var boolean
     */
    private $storeOriginal;

    /**
     * @var boolean
     */
    private $storeText;

    /**
     * @var boolean
     */
    private $builtInStopWords;

    /**
     * @var boolean
     */
    private $customStopWords;

    /**
     * @var string
     */
    private $stopWordsDatasourceKey;


    /**
     * @var string
     */
    private $stopWordsDatasourceColumn;

    /**
     * @var boolean
     */
    private $indexContent;

    /**
     * @var integer
     */
    private $minPhraseLength;

    /**
     * @var integer
     */
    private $maxPhraseLength;


    public function __construct($tableName = "", $storeOriginal = false, $storeText = false) {
        $this->storeOriginal = $storeOriginal;
        $this->storeText = $storeText;

        parent::__construct(SQLDatabaseDatasourceConfig::SOURCE_TABLE, $tableName);
    }

    /**
     * @return bool
     */
    public function isStoreOriginal() {
        return $this->storeOriginal;
    }

    /**
     * @param bool $storeOriginal
     */
    public function setStoreOriginal($storeOriginal): void {
        $this->storeOriginal = $storeOriginal;
    }

    /**
     * @return bool
     */
    public function isStoreText() {
        return $this->storeText;
    }

    /**
     * @param bool $storeText
     */
    public function setStoreText($storeText): void {
        $this->storeText = $storeText;
    }

    /**
     * @return bool
     */
    public function isBuiltInStopWords() {
        return $this->builtInStopWords;
    }

    /**
     * @param bool $builtInStopWords
     */
    public function setBuiltInStopWords($builtInStopWords): void {
        $this->builtInStopWords = $builtInStopWords;
    }

    /**
     * @return bool
     */
    public function isCustomStopWords() {
        return $this->customStopWords;
    }

    /**
     * @param bool $customStopWords
     */
    public function setCustomStopWords($customStopWords): void {
        $this->customStopWords = $customStopWords;
    }

    /**
     * @return string
     */
    public function getStopWordsDatasourceKey() {
        return $this->stopWordsDatasourceKey;
    }

    /**
     * @param string $stopWordsDatasourceKey
     */
    public function setStopWordsDatasourceKey($stopWordsDatasourceKey): void {
        $this->stopWordsDatasourceKey = $stopWordsDatasourceKey;
    }

    /**
     * @return string
     */
    public function getStopWordsDatasourceColumn() {
        return $this->stopWordsDatasourceColumn;
    }

    /**
     * @param string $stopWordsDatasourceColumn
     */
    public function setStopWordsDatasourceColumn($stopWordsDatasourceColumn): void {
        $this->stopWordsDatasourceColumn = $stopWordsDatasourceColumn;
    }

    /**
     * @return bool
     */
    public function isIndexContent() {
        return $this->indexContent;
    }

    /**
     * @param bool $indexContent
     */
    public function setIndexContent($indexContent): void {
        $this->indexContent = $indexContent;
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
    public function setMinPhraseLength($minPhraseLength): void {
        $this->minPhraseLength = $minPhraseLength;
    }

    /**
     * @return int
     */
    public function getMaxPhraseLength() {
        return $this->maxPhraseLength;
    }

    /**
     * @param int $maxPhraseLength
     */
    public function setMaxPhraseLength($maxPhraseLength): void {
        $this->maxPhraseLength = $maxPhraseLength;
    }


}
