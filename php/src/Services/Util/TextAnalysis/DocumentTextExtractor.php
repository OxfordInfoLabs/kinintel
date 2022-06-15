<?php

namespace Kinintel\Services\Util\TextAnalysis;
/**
 * @implementation text/plain \Kinintel\Services\Util\TextAnalysis\Extractors\TextTextExtractor
 * @implementation text/html \Kinintel\Services\Util\TextAnalysis\Extractors\HTMLTextExtractor
 * @implementation application/vnd.openxmlformats-officedocument.wordprocessingml.document \Kinintel\Services\Util\TextAnalysis\Extractors\DocxTextExtractor
 *
 */
interface DocumentTextExtractor {

    /**
     * Extract the text from a document supplied as a literal string
     *
     * @param $string
     * @return mixed
     */
    public function extractTextFromString($string);

    /**
     * Extract the text from a document supplied as a local file path
     *
     * @param $filePath
     * @return mixed
     */
    public function extractTextFromFile($filePath);

}
