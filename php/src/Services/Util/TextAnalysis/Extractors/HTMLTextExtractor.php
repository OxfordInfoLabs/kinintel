<?php

namespace Kinintel\Services\Util\TextAnalysis\Extractors;

use Kinikit\Core\Logging\Logger;
use Kinintel\Services\Util\TextAnalysis\DocumentTextExtractor;

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
