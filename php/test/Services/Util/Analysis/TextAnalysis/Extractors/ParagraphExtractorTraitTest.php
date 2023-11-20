
<?php

//namespace Kinintel\Test\Services\Util\Analysis\TextAnalysis\Extractors;

use Kinintel\Services\Util\Analysis\TextAnalysis\Extractors\ParagraphExtractorTrait;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\TextChunk;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";
class ParagraphExtractorTraitTest extends TestCase {
    public function testCanExtractParagraphs(){
        $fakeExtractor = new class {
            use ParagraphExtractorTrait;
            public function getExtractedParagraphs(string $text, int $minParagraphLength) {
                return $this->extractParagraphs($text, $minParagraphLength);
            }
        };

        $text1 = "hello test";
        $this->assertEquals(
            [new TextChunk("hello test", 0, 10)],
            $fakeExtractor->getExtractedParagraphs($text1, 5)
        );

        $text2 = "hello test \n\ngot you!";
        $this->assertEquals(
            [new TextChunk("hello test", 0, 10), new TextChunk("got you!", 10, 8)],
            $fakeExtractor->getExtractedParagraphs($text2, 5)
        );
    }
}