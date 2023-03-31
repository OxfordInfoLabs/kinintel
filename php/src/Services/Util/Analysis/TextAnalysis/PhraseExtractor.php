<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis;

use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\Phrase;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\StopWord;

class PhraseExtractor {

    /**
     * @var StopwordManager
     */
    private $stopwordManager;

    /**
     * @param StopwordManager $stopwordManager
     */
    public function __construct($stopwordManager) {
        $this->stopwordManager = $stopwordManager;
    }

    /**
     * Extract the phrases out of the supplied text, using the provided settings
     *
     * @param $text
     * @param $maxPhraseLength
     * @param $minPhraseLength
     * @param StopWord[] $stopWords
     * @param $language
     *
     * @return Phrase[]
     */
    public function extractPhrases($text, $maxPhraseLength = 1, $minPhraseLength = 1, $stopWords = [], $language = 'EN') {
        foreach ($stopWords as $index => $stopWord) {
            $stopWord = $this->stopwordManager->expandStopwords($stopWord, $language);
            array_splice($stopWords, $index, 1, [$stopWord]);
        }


        $allWords = $this->getWords($text);

        $phrases = [];

        for ($i = 0; $i < sizeof($allWords); $i++) {
            $word = strtolower($allWords[$i]);
            $phrase = [];
            if ($safeWord = $this->isSafeWord($word, $stopWords, 0)) {
                $phrase[] = $safeWord;
                $phrases[] = implode(" ", $phrase);
                for ($j = 1; $j < $maxPhraseLength; $j++) {
                    $nextWord = isset($allWords[$i + $j]) ? strtolower($allWords[$i + $j]) : null;
                    if ($nextWord) {
                        if ($nextSafeWord = $this->isSafeWord($nextWord, $stopWords, sizeof($phrase))) {
                            $phrase[] = $nextSafeWord;
                            $phrases[] = implode(" ", $phrase);
                        }
                    } else {
                        break;
                    }
                }
            }

        }

//        for ($i = 0; $i < sizeof($allWords); $i++) {
//            $word = strtolower($allWords[$i]);
//            $phrase = [];
//            if (!in_array($word, $stopwords)) {
//                $phrase[] = $word;
//                $phrases[] = implode(" ", $phrase);
//                for ($j = 1; $j < $maxPhraseLength; $j++) {
//                    $nextWord = isset($allWords[$i + $j]) ? strtolower($allWords[$i + $j]) : null;
//                    if ($nextWord) {
//                        $phrase[] = $nextWord;
//                        $phrases[] = implode(" ", $phrase);
//                    } else {
//                        break;
//                    }
//                }
//            }
//        }

        $finalPhrases = [];
        $phraseCounts = array_count_values($phrases);
        foreach ($phraseCounts as $phrase => $count) {
            if (sizeof(explode(" ", $phrase)) >= $minPhraseLength) {
                $finalPhrases[] = new Phrase($phrase, $count, sizeof($this->getWords($phrase)));
            }
        }

        return $finalPhrases;
    }


    private function getWords($content) {
        preg_match_all('/[\pL\']+/u', $content, $allWords);
        return $allWords[0];
    }

    /**
     * Check if the supplied word is contained in any of the stop word lists. Also check the current phrase length
     * against the supplied min length settings.
     *
     * @param string $word Lower case word to check
     * @param StopWord[] $stopWords
     * @param integer $phraseLength
     * @return string
     */
    private function isSafeWord($word, $stopWords, $phraseLength) {
        if (sizeof($stopWords) > 0) {
            $safeWord = '';
            foreach ($stopWords as $stopWord) { //For each list of stop words
                if (in_array($word, $stopWord->getList())) { //If the current word is in the list
                    //If this is not a minimal length stop word (so we keep "in correlation with" but throw out "in")
                    if ($stopWord->getMinPhraseLength() && ($phraseLength + 1 >= $stopWord->getMinPhraseLength())) {
                        $safeWord = $word;
                    } else { //return ""
                        $safeWord = '';
                        break;
                    }
                } else { //Current word is not in the list
                    $safeWord = $word;
                }
            }
            return $safeWord;
        }
        return $word;
    }
}
