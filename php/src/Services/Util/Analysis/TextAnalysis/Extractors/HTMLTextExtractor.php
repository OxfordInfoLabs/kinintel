<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;

class HTMLTextExtractor implements DocumentTextExtractor {

    use ParagraphExtractorTrait;

    /**
     * Extract the text from the supplied HTML string
     *
     * @param $string
     * @return mixed|string
     */
    public function extractRawTextFromHTMLString($string) {
        if (strlen($string) == 0) return "";

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
        $string = str_replace("<br>", "\r", $string);
        $string = strip_tags($string);
        $string = html_entity_decode($string);
        $string = urldecode($string);
        return $string;
    }

    public function extractTextFromString($string){
        $text = $this->extractRawTextFromHTMLString($string);
        return $this->flattenString($text);
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

    public function extractChunksFromFile($filePath, $minChunkLength = self::MIN_CHUNK_LENGTH) {
        $contents = file_get_contents($filePath);
        return $this->extractChunksFromString($contents, $minChunkLength);
    }

    public function extractChunksFromString($string, $minChunkLength = self::MIN_CHUNK_LENGTH){
        $text = $this->extractRawTextFromHTMLString($string);
        return $this->extractParagraphs($text, $minChunkLength);
    }

    /**
     * Removes line breaks to return a single line of words.
     * @param string $string
     * @return string
     */
    private function flattenString(string $string): string {
        //Strip weird characters
        $string = preg_replace('/[^A-Za-z0-9\-.,\'()\":;?!\/\[\]]/', ' ', $string);
        //Deduplicate spaces
        $string = preg_replace('/ +/', ' ', $string);
        $string = trim($string);
        //Throw away newlines
        $string = preg_replace("/\r|\n|\t/", "", $string);
        return $string;
    }
}
