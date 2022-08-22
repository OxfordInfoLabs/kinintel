<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;

class TextTextExtractor implements DocumentTextExtractor {

    /**
     * Return the text from the supplied string
     *
     * @param $string
     * @return mixed|void
     */
    public function extractTextFromString($string) {
        return preg_replace("/\r|\n/", "", $string);
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
}
