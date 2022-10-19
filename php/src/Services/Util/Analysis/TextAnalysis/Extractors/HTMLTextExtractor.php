<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;

class HTMLTextExtractor implements DocumentTextExtractor {

    /**
     * Extract the text from the supplied HTML string
     *
     * @param $string
     * @return mixed|string
     */
    public function extractTextFromString($string) {
        $exploded = explode("<body", $string);
        if (sizeof($exploded) > 1) {
            $string = $exploded[1];
            $string = "<body" . $string;
        }

        $string = str_replace('><', '> <', $string);

        $string = strip_tags($string);
        return preg_replace("/\r|\n/", "", $string);
    }

    /**
     * Extract the text from the supplied HTML file
     *
     * @param $filePath
     * @return mixed|string
     */
    public function extractTextFromFile($filePath) {
        $string = file_get_contents($filePath);
        return $this->extractTextFromString($string);
    }
}
