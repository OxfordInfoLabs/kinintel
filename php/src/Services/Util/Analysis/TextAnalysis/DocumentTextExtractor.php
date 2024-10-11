<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\TextChunk;

/**
 * @implementation text/plain \Kinintel\Services\Util\Analysis\TextAnalysis\Extractors\TextTextExtractor
 * @implementation text/html \Kinintel\Services\Util\Analysis\TextAnalysis\Extractors\HTMLTextExtractor
 * @implementation application/vnd.openxmlformats-officedocument.wordprocessingml.document \Kinintel\Services\Util\Analysis\TextAnalysis\Extractors\DocxTextExtractor
 * @implementation application/pdf \Kinintel\Services\Util\Analysis\TextAnalysis\Extractors\PDFTextExtractor
 *
 */
interface DocumentTextExtractor {

    const MIN_CHUNK_LENGTH = 5;

    /**
     * Extract the text from a document supplied as a literal string
     *
     * @param $string
     * @return string
     */
    public function extractTextFromString($string);

    /**
     * Extract the text from a document supplied as a local file path
     *
     * @param $filePath
     * @return string
     */
    public function extractTextFromFile($filePath);

    /**
     * Extract the text and chunk it, returning both, from a local file path
     *
     * @param $filePath
     * @param int $minChunkLength
     * @return TextChunk[]
     */
    public function extractChunksFromFile($filePath, $minChunkLength = self::MIN_CHUNK_LENGTH);

    /**
     * Extract the text and chunk it, returning both, from the (possibly encoded) contents of a document
     *
     * @param $string
     * @param int $minChunkLength
     * @return TextChunk[]
     */
    public function extractChunksFromString($string, $minChunkLength = self::MIN_CHUNK_LENGTH);

}
