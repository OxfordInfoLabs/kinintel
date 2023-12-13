<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;
use Smalot\PdfParser\Config;

class PDFTextExtractor implements DocumentTextExtractor {

    use ParagraphExtractorTrait;

    /**
     * @param $string
     * @return string
     * @throws \Exception
     */
    public function extractRawTextFromPDFContentString($string): string {
        $config = new Config();
        $config->setHorizontalOffset("");
        $config->setFontSpaceLimit(-60);
        $parser = new \Smalot\PdfParser\Parser([], $config);
        $pdf = $parser->parseContent($string);
        return $pdf->getText();
    }

    /**
     * Extract the text from the supplied string of PDF contents, removing new lines and whitespace
     *
     * @param $string
     * @return array|mixed|string|string[]|null
     */
    public function extractTextFromString($string) {
        $text = $this->extractRawTextFromPDFContentString($string);
        return preg_replace("/\r|\n|\t/", "", $text);
    }

    public function extractChunksFromFile($filePath, $minChunkLength = self::MIN_CHUNK_LENGTH) {
        $contents = file_get_contents($filePath);
        return $this->extractChunksFromString($contents, $minChunkLength);
    }

    /**
     * Extract the text from the supplied pdf file
     *
     * @param $filePath
     * @return array|mixed|string|string[]|null
     * @throws \Exception
     */
    public function extractTextFromFile($filePath) {
        $contents = file_get_contents($filePath);
        return $this->extractTextFromString($contents);
    }

    public function extractChunksFromString($string, $minChunkLength = self::MIN_CHUNK_LENGTH) {
        $text = $this->extractRawTextFromPDFContentString($string);
        return $this->extractParagraphs($text, $minChunkLength);
    }
}
