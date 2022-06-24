<?php

namespace Kinintel\Test\Services\Util\TextAnalysis;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Services\Util\TextAnalysis\PhraseExtractor;
use Kinintel\Services\Util\TextAnalysis\StopwordManager;
use Kinintel\ValueObjects\Util\TextAnalysis\Phrase;

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

        $this->stopwordManager->returnValue("getStopwordsByLanguage", [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ], ["EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"));

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

        $this->stopwordManager->returnValue("getStopwordsByLanguage", [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ], ["EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 3);
print_r($phrases);
        $this->assertEquals([
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
            new Phrase("over", 1, 1),
            new Phrase("lazy", 2, 1),
            new Phrase("lazy dog", 1, 2),
            new Phrase("dog", 1, 1),
            new Phrase("ain't", 1, 1),
            new Phrase("average", 1, 1),
            new Phrase("average lazy", 1, 2),
            new Phrase("average lazy test", 1, 3),
            new Phrase("lazy test", 1, 2),
            new Phrase("test", 1, 1),
            new Phrase("quick brown one", 1, 3),
            new Phrase("brown one", 1, 2),
            new Phrase("one", 1, 1)
        ], $phrases);

    }

    public function testCanExtractMultipleWordPhrasesWithMultipleMinimumLengthFromPassedTextUsingBuiltInStopwords() {

        $this->stopwordManager->returnValue("getStopwordsByLanguage", [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ], ["EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 3, 2);

        $this->assertEquals([
            new Phrase("quick brown", 2, 2),
            new Phrase("quick brown fox", 1, 3),
            new Phrase("brown fox", 1, 2),
            new Phrase("brown fox jumped", 1, 3),
            new Phrase("fox jumped", 1, 2),
            new Phrase("fox jumped over", 1, 3),
            new Phrase("jumped over", 1, 2),
            new Phrase("lazy dog", 1, 2),
            new Phrase("average lazy", 1, 2),
            new Phrase("average lazy test", 1, 3),
            new Phrase("lazy test", 1, 2),
            new Phrase("quick brown one", 1, 3),
            new Phrase("brown one", 1, 2)
        ], $phrases);

    }

    public function testCanExtractSingleWordsFromPassedTextUsingBuiltInStopwordsAndCustomStopwords() {

        $this->stopwordManager->returnValue("getStopwordsByLanguage", [
            "it's",
            "the",
            "a",
            "this",
            "your"
        ], ["EN"]);

        $phrases = $this->phraseExtractor->extractPhrases(file_get_contents(__DIR__ . "/example.txt"), 1, 1, true, [
            "ain't", "test", "one", "over"
        ]);

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

}
