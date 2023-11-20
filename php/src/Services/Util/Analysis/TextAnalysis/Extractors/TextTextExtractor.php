<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;

class TextTextExtractor implements DocumentTextExtractor {

    use ParagraphExtractorTrait;

    /**
     * Return the text from the supplied string
     *
     * @param $string
     * @return mixed|void
     */
    public function extractTextFromString($string) {
        return preg_replace("/\r|\n|\t/", "", $string);
    }

    /**
     * Load the supplied file and return its contents
     *
     * @param $filePath
     * @return mixed|void
     */
    public function extractTextFromFile($filePath) {
        $string = file_get_contents($filePath);
        return $this->extractTextFromString($string);
    }

    public function extractChunksFromFile($filePath, $minChunkLength = self::MIN_CHUNK_LENGTH) {
        $contents = file_get_contents($filePath);
        return $this->extractChunksFromString($contents);
    }

    public function extractChunksFromString($string, $minChunkLength = self::MIN_CHUNK_LENGTH) {
        return $this->extractParagraphs($string, $minChunkLength);
    }
}
