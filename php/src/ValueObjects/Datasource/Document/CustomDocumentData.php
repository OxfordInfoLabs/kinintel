<?php


namespace Kinintel\ValueObjects\Datasource\Document;


use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\Phrase;

class CustomDocumentData {

    /**
     * Additional document data matching additional data fields defined for a custom document parser.
     *
     * @var mixed[string]
     */
    private $additionalDocumentData;


    /**
     * Array of array of phrases keyed in by section where each entry is usually the result of
     * a call to the PhraseExtractor.
     *
     * @var Phrase[string][]
     */
    private $allPhrasesIndexedBySection;

    /**
     * CustomDocumentData constructor.
     *
     * @param mixed[string] $additionalDocumentData
     * @param Phrase[string][] $phrasesBySection
     */
    public function __construct($additionalDocumentData = [], $phrasesBySection = []) {
        $this->additionalDocumentData = $additionalDocumentData;
        $this->allPhrasesIndexedBySection = $phrasesBySection;
    }


    /**
     * @return mixed[string]
     */
    public function getAdditionalDocumentData() {
        return $this->additionalDocumentData;
    }

    /**
     * @param mixed[string] $additionalDocumentData
     */
    public function setAdditionalDocumentData($additionalDocumentData) {
        $this->additionalDocumentData = $additionalDocumentData;
    }

    /**
     * @return Phrase[string][]
     */
    public function getAllPhrasesIndexedBySection() {
        return $this->allPhrasesIndexedBySection;
    }

    /**
     * @param Phrase[string][] $phrasesBySection
     */
    public function setAllPhrasesIndexedBySection($allPhrasesIndexedBySection) {
        $this->allPhrasesIndexedBySection = $allPhrasesIndexedBySection;
    }

    /**
     * Set phrases for a given section (defaults to Main to represent whole document)
     *
     * @param Phrase[] $phrases
     * @param string $sectionKey
     */
    public function setPhrasesForSection($phrases, $sectionKey = "Main") {
        $this->allPhrasesIndexedBySection[$sectionKey] = $phrases;
    }


}
