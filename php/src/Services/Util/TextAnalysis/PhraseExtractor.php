<?php

namespace Kinintel\Services\Util\TextAnalysis;

use Kinikit\Core\Logging\Logger;
use Kinintel\ValueObjects\Util\TextAnalysis\Phrase;

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
     * @param $useBuiltInStopWords
     * @param $customStopWords
     * @param $language
     *
     * @return Phrase[]
     */
    public function extractPhrases($text, $maxPhraseLength = 1, $minPhraseLength = 1, $useBuiltInStopWords = true, $customStopWords = [], $language = 'EN') {
        $stopwords = $useBuiltInStopWords ? $this->stopwordManager->getStopwordsByLanguage($language) : [];

        if ($customStopWords) {
            $stopwords = array_merge($stopwords, $customStopWords);
        }

        $allWords = $this->getWords($text);

        $phrases = [];

        for ($i = 0; $i < sizeof($allWords); $i++) {
            $word = strtolower($allWords[$i]);
            $phrase = [];
            if (!in_array($word, $stopwords)) {
                $phrase[] = $word;
                $phrases[] = implode(" ", $phrase);
                for ($j = 1; $j < $maxPhraseLength; $j++) {
                    $nextWord = isset($allWords[$i + $j]) ? strtolower($allWords[$i + $j]) : null;
                    if ($nextWord && !in_array($nextWord, $stopwords)) {
                        $phrase[] = $nextWord;
                        $phrases[] = implode(" ", $phrase);
                    } else {
                        break;
                    }
                }
            }
        }

        $finalPhrases = [];
        $phraseCounts = array_count_values($phrases);
        foreach ($phraseCounts as $phrase => $count) {
            if (sizeof(explode(" ", $phrase)) >= $minPhraseLength) {
                $finalPhrases[] = new Phrase($phrase, $count);
            }
        }

        return $finalPhrases;
    }


    private function getWords($content) {
        preg_match_all('/[\pL\']+/u', $content, $allWords);
        return $allWords[0];
    }
}
