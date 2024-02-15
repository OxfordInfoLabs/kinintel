<?php

namespace Kinintel\Test\Services\Util\Analysis\TextAnalysis;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Services\Util\Analysis\TextAnalysis\PhraseExtractor;
use Kinintel\Services\Util\Analysis\TextAnalysis\StopwordManager;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\Phrase;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\StopWord;

include_once "autoloader.php";

class PhraseExtractorTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var PhraseExtractor
     */
    private $phraseExtractor;

    /**
     * @var MockObject
     */
    private $stopwordManager;

    public function setUp(): void {
        $this->stopwordManager = MockObjectProvider::instance()->getMockInstance(StopwordManager::class);
        $this->phraseExtractor = new PhraseExtractor($this->stopwordManager);
    }


    public function testCanExtractSingleWordsFromPassedTextUsingBuiltInStopwords() {

        $stopword = new StopWord(true, null, null, null, null);
        $doctoredStopwords = new StopWord(true, null, null, null, null, [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ]);

        $this->stopwordManager->returnValue("expandStopwords", $doctoredStopwords, [$stopword, "EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 1, 1, [$stopword]);

        $this->assertEquals([
            new Phrase("quick", 2, 1),
            new Phrase("brown", 2, 1),
            new Phrase("fox", 1, 1),
            new Phrase("jumped", 1, 1),
            new Phrase("over", 1, 1),
            new Phrase("lazy", 2, 1),
            new Phrase("dog", 1, 1),
            new Phrase("ain't", 1, 1),
            new Phrase("average", 1, 1),
            new Phrase("test", 1, 1),
            new Phrase("one", 1, 1)
        ], $phrases);

    }

    public function testCanExtractMultipleWordPhrasesFromPassedTextUsingBuiltInStopwords() {

        $stopWord = new StopWord(true, null, null, null, 2);
        $doctoredStopwords = new StopWord(true, null, null, null, 2, [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ]);

        $this->stopwordManager->returnValue("expandStopwords", $doctoredStopwords, [$stopWord, "EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 3, 1, [$stopWord]);

        $expectedPhrases = [
            new Phrase("quick", 2, 1),
            new Phrase("quick brown", 2, 2),
            new Phrase("quick brown fox", 1, 3),
            new Phrase("brown", 2, 1),
            new Phrase("brown fox", 1, 2),
            new Phrase("brown fox jumped", 1, 3),
            new Phrase("fox", 1, 1),
            new Phrase("fox jumped", 1, 2),
            new Phrase("fox jumped over", 1, 3),
            new Phrase("jumped", 1, 1),
            new Phrase("jumped over", 1, 2),
            new Phrase("jumped over the", 1, 3),
            new Phrase("over", 1, 1),
            new Phrase("over the", 1, 2),
            new Phrase("over the lazy", 1, 3),
            new Phrase("lazy", 2, 1),
            new Phrase("lazy dog", 1, 2),
            new Phrase("lazy dog this", 1, 3),
            new Phrase("dog", 1, 1),
            new Phrase("dog this", 1, 2),
            new Phrase("dog this ain't", 1, 3),
            new Phrase("ain't", 1, 1),
            new Phrase("ain't your", 1, 2),
            new Phrase("ain't your average", 1, 3),
            new Phrase("average", 1, 1),
            new Phrase("average lazy", 1, 2),
            new Phrase("average lazy test", 1, 3),
            new Phrase("lazy test", 1, 2),
            new Phrase("lazy test it's", 1, 3),
            new Phrase("test", 1, 1),
            new Phrase("test it's", 1, 2),
            new Phrase("test it's a", 1, 3),
            new Phrase("quick brown one", 1, 3),
            new Phrase("brown one", 1, 2),
            new Phrase("one", 1, 1)
        ];
        sort($expectedPhrases);
        sort($phrases);

        $this->assertEquals($expectedPhrases, $phrases);

    }

    public function testCanExtractMultipleWordPhrasesWithMultipleMinimumLengthFromPassedTextUsingBuiltInStopwords() {

        $stopWord = new StopWord(true, null, null, null, 2);
        $doctoredStopwords = new StopWord(true, null, null, null, 2, [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ]);

        $this->stopwordManager->returnValue("expandStopwords", $doctoredStopwords, [$stopWord, "EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 3, 2, [$stopWord]);

        $expectedPhrases = [
            new Phrase("quick brown", 2, 2),
            new Phrase("quick brown fox", 1, 3),
            new Phrase("brown fox", 1, 2),
            new Phrase("brown fox jumped", 1, 3),
            new Phrase("fox jumped", 1, 2),
            new Phrase("fox jumped over", 1, 3),
            new Phrase("jumped over", 1, 2),
            new Phrase("jumped over the", 1, 3),
            new Phrase("over the", 1, 2),
            new Phrase("over the lazy", 1, 3),
            new Phrase("lazy dog", 1, 2),
            new Phrase("lazy dog this", 1, 3),
            new Phrase("dog this", 1, 2),
            new Phrase("dog this ain't", 1, 3),
            new Phrase("ain't your", 1, 2),
            new Phrase("ain't your average", 1, 3),
            new Phrase("average lazy", 1, 2),
            new Phrase("average lazy test", 1, 3),
            new Phrase("lazy test", 1, 2),
            new Phrase("lazy test it's", 1, 3),
            new Phrase("test it's", 1, 2),
            new Phrase("test it's a", 1, 3),
            new Phrase("quick brown one", 1, 3),
            new Phrase("brown one", 1, 2),
        ];
        sort($expectedPhrases);
        sort($phrases);

        $this->assertEquals($expectedPhrases, $phrases);

    }

    public function testCanExtractSingleWordsFromPassedTextUsingBuiltInStopwordsAndCustomStopwords() {

        $stopWordOne = new StopWord(true, null, null, null, null);
        $doctoredStopwordsOne = new StopWord(true, null, null, null, null, [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ]);

        $stopWordTwo = new StopWord(null, true, null, null, null, ["ain't", "test", "one", "over"]);


        $this->stopwordManager->returnValue("expandStopwords", $doctoredStopwordsOne, [$stopWordOne, "EN"]);
        $this->stopwordManager->returnValue("expandStopwords", $stopWordTwo, [$stopWordTwo, "EN"]);


        $stopWords = [$stopWordOne, $stopWordTwo];

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 1, 1, $stopWords);

        $this->assertEquals([
            new Phrase("quick", 2, 1),
            new Phrase("brown", 2, 1),
            new Phrase("fox", 1, 1),
            new Phrase("jumped", 1, 1),
            new Phrase("lazy", 2, 1),
            new Phrase("dog", 1, 1),
            new Phrase("average", 1, 1)
        ], $phrases);

    }

    public function testCanExtractMultiWordPhrasesAllowingForStopWordsToBeIncludedInPhrasesWithAMinLength() {

        $stopWord = new StopWord(true, null, null, null, 2);
        $doctoredStopWord = new StopWord(true, null, null, null, 2, [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ]);

        $this->stopwordManager->returnValue("expandStopwords", $doctoredStopWord, [$stopWord, "EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 3, 2, [$stopWord]);

        $expectedPhrases = [
            new Phrase("quick brown", 2, 2),
            new Phrase("quick brown fox", 1, 3),
            new Phrase("brown fox", 1, 2),
            new Phrase("brown fox jumped", 1, 3),
            new Phrase("fox jumped", 1, 2),
            new Phrase("fox jumped over", 1, 3),
            new Phrase("jumped over", 1, 2),
            new Phrase("jumped over the", 1, 3),
            new Phrase("over the", 1, 2),
            new Phrase("over the lazy", 1, 3),
            new Phrase("lazy dog", 1, 2),
            new Phrase("lazy dog this", 1, 3),
            new Phrase("dog this", 1, 2),
            new Phrase("dog this ain't", 1, 3),
            new Phrase("ain't your", 1, 2),
            new Phrase("ain't your average", 1, 3),
            new Phrase("average lazy", 1, 2),
            new Phrase("average lazy test", 1, 3),
            new Phrase("lazy test", 1, 2),
            new Phrase("lazy test it's", 1, 3),
            new Phrase("test it's", 1, 2),
            new Phrase("test it's a", 1, 3),
            new Phrase("quick brown one", 1, 3),
            new Phrase("brown one", 1, 2),
        ];
        sort($expectedPhrases);
        sort($phrases);

        $this->assertEquals($expectedPhrases, $phrases);
    }

    public function testCanExtractMultipleWordsFromPassedTextUsingBuiltInStopwordsAndCustomStopwordsWithAMinLength() {

        $stopWordOne = new StopWord(true, null, null, null, null);
        $stopWordTwo = new StopWord(null, true, null, null, 2, ["ain't", "test", "one", "over"]);

        $doctoredStopwordsOne = new StopWord(true, null, null, null, null, [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ]);

        $this->stopwordManager->returnValue("expandStopwords", $doctoredStopwordsOne, [$stopWordOne, "EN"]);
        $this->stopwordManager->returnValue("expandStopwords", $stopWordTwo, [$stopWordTwo, "EN"]);

        $stopWords = [
            $stopWordOne,
            $stopWordTwo
        ];

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 2, 1, $stopWords);

        $expectedPhrases = [
            new Phrase("quick", 2, 1),
            new Phrase("quick brown", 2, 2),
            new Phrase("brown", 2, 1),
            new Phrase("brown fox", 1, 2),
            new Phrase("fox", 1, 1),
            new Phrase("fox jumped", 1, 2),
            new Phrase("jumped", 1, 1),
            new Phrase("jumped over", 1, 2),
            new Phrase("lazy", 2, 1),
            new Phrase("lazy dog", 1, 2),
            new Phrase("dog", 1, 1),
            new Phrase("average", 1, 1),
            new Phrase("average lazy", 1, 2),
            new Phrase("lazy test", 1, 2),
            new Phrase("brown one", 1, 2),
        ];
        sort($expectedPhrases);
        sort($phrases);
        $this->assertEquals($expectedPhrases, $phrases);

    }

    public function testCanExtractStopWordsFromCustomDatasource() {

        $stopWord = new StopWord(false, true, "testKey", "testColumn");
        $doctoredStopWord = new StopWord(true, null, null, null, 2, [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ]);

        $this->stopwordManager->returnValue("expandStopwords", $doctoredStopWord, [$stopWord, "EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 1, 1, [$stopWord]);

        $this->assertEquals([
            new Phrase("quick", 2, 1),
            new Phrase("brown", 2, 1),
            new Phrase("fox", 1, 1),
            new Phrase("jumped", 1, 1),
            new Phrase("over", 1, 1),
            new Phrase("lazy", 2, 1),
            new Phrase("dog", 1, 1),
            new Phrase("ain't", 1, 1),
            new Phrase("average", 1, 1),
            new Phrase("test", 1, 1),
            new Phrase("one", 1, 1)
        ], $phrases);
    }

//    public function testSpeedOfPhraseExtractor(){
//        $vocabulary = [];
//        $testText = "";
//        for ($i = 0; $i < 100; $i++) {
//            $vocabulary[] = "word$i";
//        }
//        $vocabChoices = array_flip($vocabulary);
//        for ($j = 0; $j < 100000; $j++) {
//            $testText .= array_rand($vocabChoices) . " ";
//        }
//
//        $t1 = microtime(true);
//        $oldPhrases = $this->phraseExtractor->extractPhrases($testText, 5);
//        $t2 = microtime(true);
//
//        print_r($t2 - $t1);
//        $this->assertTrue(true);
//
////        $t3 = microtime(true);
////        $newPhrases = $this->phraseExtractor->extractPhrasesNew($testText, 5);
////        $t4 = microtime(true);
////
////        echo "\n";
////        print_r($t4 - $t3);
////
////        $this->assertEquals($oldPhrases, $newPhrases);
//
//    }
//
//    public function testSpeedOfPhraseExtractorWithStopwords(){
//        $stopWord = new StopWord(false, true, "testKey", "testColumn");
//        $ignoredWords = [];
//        for ($i = 0; $i < 1; $i++) {
//            $ignoredWords[] = "word$i";
//        }
//        $doctoredStopWord = new StopWord(true, null, null, null, 2, $ignoredWords);
//
//        $this->stopwordManager->returnValue("expandStopwords", $doctoredStopWord, [$stopWord, "EN"]);
//
//        $vocabulary = [];
//        for ($i = 0; $i < 100; $i++) {
//            $vocabulary[] = "word$i";
//        }
//        $vocabChoices = array_flip($vocabulary);
//
//        $testText = "";
//        for ($j = 0; $j < 100000; $j++) {
//            $testText .= array_rand($vocabChoices) . " ";
//        }
//
//        $t1 = microtime(true);
//        $phrases = $this->phraseExtractor->extractPhrases($testText, 5, 1, []);
//        $t2 = microtime(true);
//
//        print_r($t2 - $t1);
//
//
//        $t3 = microtime(true);
//        $stoppedPhrases = $this->phraseExtractor->extractPhrases($testText, 5, 1, [$stopWord]);
//        $t4 = microtime(true);
//
//        echo "\n";
//        print_r($t4 - $t3);
//
//        $this->assertTrue(true);
//    }

}
