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
        if (strlen($string) > 0) {
            $excludedTags = [
                "script",
                "style"
            ];

            $cleanDom = new \DOMDocument();
            $dom = new \DOMDocument();
            $dom->loadHTML($string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

            $body = $dom->getElementsByTagName("body")->item(0);
            if ($body && $body->hasChildNodes()) {
                foreach ($body->childNodes as $childNode) {
                    $cleanDom->appendChild($cleanDom->importNode($childNode, true));
                }

                $removals = [];
                foreach ($excludedTags as $excludedTag) {
                    $tags = $cleanDom->getElementsByTagName($excludedTag);

                    foreach ($tags as $item) {
                        $removals[] = $item;
                    }
                }

                foreach ($removals as $item) {
                    $item->parentNode->removeChild($item);
                }

                $string = $cleanDom->saveHTML();
            }

            $string = str_replace('><', '> <', $string);
            $string = strip_tags($string);
            $string = html_entity_decode($string);
            $string = urldecode($string);
            $string = preg_replace('/[^A-Za-z0-9\-.,\'()\":;?!\/\[\]]/', ' ', $string);
            $string = preg_replace('/ +/', ' ', $string);
            $string = trim($string);
            $string = preg_replace("/\r|\n|\t/", "", $string);
        }

        return $string;
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
