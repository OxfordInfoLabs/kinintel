<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;
use Smalot\PdfParser\Config;

class PDFTextExtractor implements DocumentTextExtractor {

    /**
     * Extract the text from the supplied string
     *
     * @param $string
     * @return array|mixed|string|string[]|null
     */
    public function extractTextFromString($string) {
        $config = new Config();
        $config->setHorizontalOffset("");
        $config->setFontSpaceLimit(-60);
        $parser = new \Smalot\PdfParser\Parser([], $config);
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
        $config = new Config();
        $config->setHorizontalOffset("");
        $config->setFontSpaceLimit(-60);
        $parser = new \Smalot\PdfParser\Parser([], $config);
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        return preg_replace("/\r|\n/", "", $text);
    }
}
