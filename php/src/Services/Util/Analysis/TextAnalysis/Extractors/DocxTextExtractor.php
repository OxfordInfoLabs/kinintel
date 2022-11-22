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
        $striped_content = '';
        $content = '';

        $zip = zip_open($filePath);

        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {

            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            zip_entry_close($zip_entry);
        }

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $striped_content = strip_tags($content);

        return preg_replace("/\r|\n|\t/", "", $striped_content);
    }
}
