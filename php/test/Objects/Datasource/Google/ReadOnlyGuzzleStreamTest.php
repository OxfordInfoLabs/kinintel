<?php

namespace Kinintel\Objects\Datasource\Google;

use GuzzleHttp\Psr7\Stream;
use Kinikit\Core\Stream\StreamException;
use PHPUnit\Framework\TestCase;


include_once "autoloader.php";

class ReadOnlyGuzzleStreamTest extends TestCase {

    public function testCanReadStreamGivenLength() {

        $guzzleStream = new Stream(fopen(__DIR__ . "/test1.csv", "r"));
        $stream = new ReadOnlyGuzzleStream($guzzleStream);

        $this->assertEquals("John", $stream->read(4));
        $this->assertEquals(",30\n", $stream->read(4));
        $this->assertEquals("James", $stream->read(5));

        $stream->read(1000);

        try {
            $stream->read(100);
            $this->fail("Should have thrown here");
        } catch (StreamException $e) {
            $this->assertEquals("Cannot read bytes as end of stream reached", $e->getMessage());
        }

    }

    public function testCanDetectEndOfFile() {

        $guzzleStream = new Stream(fopen(__DIR__ . "/test1.csv", "r"));
        $stream = new ReadOnlyGuzzleStream($guzzleStream);

        $this->assertFalse($stream->isEof());
        $stream->read(2048);

        $this->assertTrue($stream->isEof());

    }

    public function testCanCloseFile() {

        $guzzleStream = new Stream(fopen(__DIR__ . "/test1.csv", "r"));
        $stream = new ReadOnlyGuzzleStream($guzzleStream);

        $this->assertTrue($stream->isOpen());
        $stream->close();

        $this->assertFalse($stream->isOpen());

        try {
            $stream->read(100);
            $this->fail("Should have thrown here");
        } catch (StreamException $e) {
            $this->assertEquals("The stream has been closed", $e->getMessage());
        }

    }

    public function testCanGetContents() {

        $guzzleStream = new Stream(fopen(__DIR__ . "/test1.csv", "r"));
        $stream = new ReadOnlyGuzzleStream($guzzleStream);

        $this->assertEquals("John,30\nJames,22\nRobert,50\nWilliam,42", $stream->getContents());

    }

    public function testCanReadLineByLine() {

        $guzzleStream = new Stream(fopen(__DIR__ . "/test1.csv", "r"));
        $stream = new ReadOnlyGuzzleStream($guzzleStream);

        $this->assertEquals("John,30", $stream->readLine());
        $this->assertEquals("James,22", $stream->readLine());
        $this->assertEquals("Robert,50", $stream->readLine());
        $this->assertEquals("William,42", $stream->readLine());

    }

    public function testCanReadByCSVLineGivenSeparatorAndEnclosure() {

        $guzzleStream = new Stream(fopen(__DIR__ . "/test2.csv", "r"));
        $stream = new ReadOnlyGuzzleStream($guzzleStream);

        $this->assertEquals([
            "Big in, Japan",
            "Alphaville",
            1984
        ], $stream->readCSVLine("|", '"'));

        $this->assertEquals([
            "Disco 2000",
            "Pulp",
            1995
        ], $stream->readCSVLine("|", '"'));

        $this->assertEquals([
            "Dignity, Bingo",
            "Deacon Blue",
            1987
        ], $stream->readCSVLine("|", '"'));

    }



}