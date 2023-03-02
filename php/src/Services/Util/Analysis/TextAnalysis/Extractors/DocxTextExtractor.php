<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;

class DocxTextExtractor implements DocumentTextExtractor {

    public function extractTextFromString($string) {
        $tmpDir = sys_get_temp_dir();
        $filePath = tempnam($tmpDir, "docx");
        file_put_contents($filePath, $string);
        return $this->extractTextFromFile($filePath);
    }

    public function extractTextFromFile($filePath) {

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
        $striped_content = strip_tags($content);

        return preg_replace("/\r|\n|\t/", "", $striped_content);
    }
}
