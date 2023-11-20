<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;

class DocxTextExtractor implements DocumentTextExtractor {

    use ParagraphExtractorTrait;

    public function extractTextFromString($string) {
        $tmpDir = sys_get_temp_dir();
        $filePath = tempnam($tmpDir, "docx");
        file_put_contents($filePath, $string);
        return $this->extractTextFromFile($filePath);
    }

    public function extractTextFromFile($filePath) {
        $strippedContent = $this->extractRawTextFromFile($filePath);
        $flatText = preg_replace("/\r|\n|\t/", "", $strippedContent);
        return $flatText;
    }

    public function extractRawTextFromFile($filePath) {
        $content = '';

        $zipArchive = new \ZipArchive();

        $open = $zipArchive->open($filePath);

        if (!$open) return false;

        for ($i = 0; $i < $zipArchive->numFiles; $i++) {

            // Grab filename
            $fileName = $zipArchive->getNameIndex($i);

            // Continue until we get to the document itself
            if ($fileName != "word/document.xml") continue;

            // Append content
            $content .= $zipArchive->getFromIndex($i);
        }

        $zipArchive->close();

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $strippedContent = strip_tags($content);

        return $strippedContent;
    }

    public function extractChunksFromFile($filePath, $minChunkLength = self::MIN_CHUNK_LENGTH) {
        $text = $this->extractRawTextFromFile($filePath);
        $chunks = $this->extractParagraphs($text, $minChunkLength);
        return $chunks;
    }

    public function extractChunksFromString($string, $minChunkLength = self::MIN_CHUNK_LENGTH) {
        $tmpDir = sys_get_temp_dir();
        $filePath = tempnam($tmpDir, "docx");
        file_put_contents($filePath, $string);
        $text = $this->extractRawTextFromFile($filePath);
        return $this->extractParagraphs($text, $minChunkLength);
    }
}
