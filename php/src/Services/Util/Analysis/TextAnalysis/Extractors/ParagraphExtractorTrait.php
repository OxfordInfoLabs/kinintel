<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\TextChunk;

trait ParagraphExtractorTrait {

    /**
     * @param string $text
     * @param int $minParagraphLength
     * @return TextChunk[]
     */
    protected function extractParagraphs(string $text, int $minParagraphLength) {
        $paragraphBreaksRegex = "/(\s*\n\s*\n\s*)|(\s*\n\s*\t\s*)/";
        $normalisedSplitText = preg_replace("$paragraphBreaksRegex","\r", $text);

        $paragraphs = [];
        $lastCarriageReturnOffset = 0;

        //Search through the text for carriage returns, and split up the text on these

        $end = false;
        while (true) {
            $carriageReturnPos = strpos($normalisedSplitText, "\r", $lastCarriageReturnOffset+1);
            if(!$carriageReturnPos){
                $end = true;
                $carriageReturnPos = strlen($normalisedSplitText);
            };
            //Pull out the text between the previous and next carriage return
            $paragraphText = substr($normalisedSplitText, $lastCarriageReturnOffset, $carriageReturnPos - $lastCarriageReturnOffset);

            //Throw away extra whitespace
            $paragraphText = preg_replace("/\r|\n|\t/", "", $paragraphText);

            //Deduplicate spaces
            $paragraphText = preg_replace("/ +/", " ", $paragraphText);

            //If paragraph is of meaningful length, add it
            if ($carriageReturnPos - $lastCarriageReturnOffset >= $minParagraphLength){

                $paragraphs[] = new TextChunk($paragraphText, $lastCarriageReturnOffset, strlen($paragraphText));
            }

            $lastCarriageReturnOffset = $carriageReturnPos;
            if ($end) break;
        }

        return $paragraphs;
    }
}