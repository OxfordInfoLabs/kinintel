<?php

namespace Kinintel\Services\Util\TextAnalysis\Extractors;

use Kinikit\Core\Logging\Logger;
use Kinintel\Services\Util\TextAnalysis\DocumentTextExtractor;

class PDFTextExtractor implements DocumentTextExtractor {

    /**
     * Extract the text from the supplied string
     *
     * @param $string
     * @return array|mixed|string|string[]|null
     */
    public function extractTextFromString($string) {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($string);
        $text = $pdf->getText();
        return preg_replace("/\r|\n/", "", $text);
    }

    /**
     * Extract the text from the supplied pdf file
     *
     * @param $filePath
     * @return array|mixed|string|string[]|null
     * @throws \Exception
     */
    public function extractTextFromFile($filePath) {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        return preg_replace("/\r|\n/", "", $text);
    }
}
