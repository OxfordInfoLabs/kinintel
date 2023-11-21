<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\Document;

use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\StopWord;

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
     * @var StopWord[]
     */
    private $stopWords;

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

    /**
     * @var boolean
     */
    private $chunkContent;

    /**
     * Whether to store embeddings of the chunks of text in the document
     * @var boolean
     */
    private $indexChunksByAI;

    /**
     * @var string
     */
    private $customDocumentParser;


    /**
     * @param string $tableName
     * @param boolean $storeOriginal
     * @param string $storeText
     * @param bool $indexContent
     * @param StopWord[] $stopWords
     * @param integer $minPhraseLength
     * @param integer $maxPhraseLength
     * @param string $customDocumentParser
     * @param bool $chunkContent
     * @param bool $indexChunksByAI
     */
    public function __construct($tableName = "", $storeOriginal = false, $storeText = false, $indexContent = false, $stopWords = [], $minPhraseLength = 1, $maxPhraseLength = 1,
                                $customDocumentParser = null, $chunkContent = false, $indexChunksByAI = false) {
        $this->storeOriginal = $storeOriginal;
        $this->storeText = $storeText;
        $this->indexContent = $indexContent;
        $this->indexChunksByAI = $indexChunksByAI;
        $this->stopWords = $stopWords;
        $this->minPhraseLength = $minPhraseLength;
        $this->maxPhraseLength = $maxPhraseLength;
        $this->customDocumentParser = $customDocumentParser;

        parent::__construct(SQLDatabaseDatasourceConfig::SOURCE_TABLE, $tableName);
        $this->chunkContent = $chunkContent;
    }

    public function isChunkContent(): bool {
        return $this->chunkContent;
    }

    public function setChunkContent($chunkContent): void {
        $this->chunkContent = $chunkContent;
    }

    public function isIndexChunksByAI() {
        return $this->indexChunksByAI;
    }

    public function setIndexChunksByAI($indexChunksByAI): void {
        $this->indexChunksByAI = $indexChunksByAI;
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

    /**
     * @return StopWord[]
     */
    public function getStopWords() {
        return $this->stopWords;
    }

    /**
     * @param StopWord[] $stopWords
     */
    public function setStopWords($stopWords) {
        $this->stopWords = $stopWords;
    }

    /**
     * @return string
     */
    public function getCustomDocumentParser() {
        return $this->customDocumentParser;
    }

    /**
     * @param string $customDocumentParser
     */
    public function setCustomDocumentParser($customDocumentParser) {
        $this->customDocumentParser = $customDocumentParser;
    }

    /**
     * Override manage table structure to force true
     *
     * @return bool
     */
    public function isManageTableStructure() {
        return true;
    }


}
