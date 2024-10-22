<?php


namespace Kinintel\Services\Datasource\Processing\Compression;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinintel\Exception\DatasourceCompressionException;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration\ZipCompressorConfiguration;

include_once "autoloader.php";

class ZipCompressorTest extends TestBase {

    /**
     * @var ZipCompressor
     */
    private $compressor;

    public function setUp(): void {
        $this->compressor = Container::instance()->get(ZipCompressor::class);
    }

    public function testDatasetCompressionExceptionRaisedIfInputStreamNotAZip() {

        $zipStream = new ReadOnlyFileStream(__DIR__ . "/bad.zip");

        try {
            $this->compressor->uncompress($zipStream, new ZipCompressorConfiguration("bad.csv"));
            $this->fail("Should have thrown here");
        } catch (DatasourceCompressionException $e) {
            $this->assertTrue(true);
        }

    }


    public function testDatasetCompressionExceptionRaisedIfSuppliedEntryFilenameNotInZip() {


        $zipStream = new ReadOnlyFileStream(__DIR__ . "/test-compressed.zip");

        try {
            $this->compressor->uncompress($zipStream, new ZipCompressorConfiguration("bad.csv"));
            $this->fail("Should have thrown here");
        } catch (DatasourceCompressionException $e) {
            $this->assertTrue(true);
        }

    }


    public function testDatasetCompressionExceptionRaisedIfSuppliedEntryFilenamesSuppliedAndAnyNotInZip() {


        $zipStream = new ReadOnlyFileStream(__DIR__ . "/test-combined-compressed.zip");

        try {
            $this->compressor->uncompress($zipStream, new ZipCompressorConfiguration(null, ["test.csv", "bad.csv"]));
            $this->fail("Should have thrown here");
        } catch (DatasourceCompressionException $e) {
            $this->assertTrue(true);
        }

    }


    public function testCanUncompressInputStreamAndReturnPlainStreamForSingleEntryFile() {


        $zipStream = new ReadOnlyFileStream(__DIR__ . "/test-compressed.zip");

        // Call uncompress
        $stream = $this->compressor->uncompress($zipStream, new ZipCompressorConfiguration("test.csv"));

        // Now read the stream
        $this->assertEquals(["Name", "Age", "Shoe Size"], $stream->readCSVLine(","));
        $this->assertEquals(["Mark", 50, 10], $stream->readCSVLine(","));
        $this->assertEquals(["Bob", 20, 9], $stream->readCSVLine(","));
        $this->assertEquals(["Mary", 30, 7], $stream->readCSVLine(","));


    }

    public function testCanInferFilenameOfZip() {
        $zipStream = new ReadOnlyFileStream(__DIR__ . "/test-compressed.zip");

        // Call uncompress
        $stream = $this->compressor->uncompress($zipStream, new ZipCompressorConfiguration());

        $this->assertEquals(["Name", "Age", "Shoe Size"], $stream->readCSVLine(","));
        $this->assertEquals(["Mark", 50, 10], $stream->readCSVLine(","));
        $this->assertEquals(["Bob", 20, 9], $stream->readCSVLine(","));
        $this->assertEquals(["Mary", 30, 7], $stream->readCSVLine(","));
    }

    public function testCanUncompressInputStreamAndReturnJoinedPlainStreamForMultipleEntryFiles() {

        $zipStream = new ReadOnlyFileStream(__DIR__ . "/test-combined-compressed.zip");

        // Call uncompress
        $stream = $this->compressor->uncompress($zipStream, new ZipCompressorConfiguration(null, ["test.csv", "test2.csv"]));

        // Now read the stream
        $this->assertEquals(["Name", "Age", "Shoe Size"], $stream->readCSVLine(","));
        $this->assertEquals(["Mark", 50, 10], $stream->readCSVLine(","));
        $this->assertEquals(["Bob", 20, 9], $stream->readCSVLine(","));
        $this->assertEquals(["Mary", 30, 7], $stream->readCSVLine(","));
        $this->assertEquals([null], $stream->readCSVLine(","));
        $this->assertEquals(["Name", "Age", "Shoe Size"], $stream->readCSVLine(","));
        $this->assertEquals(["Jane", 60, 10], $stream->readCSVLine(","));
        $this->assertEquals(["Peter", 10, 9], $stream->readCSVLine(","));
        $this->assertEquals(["Clive", 28, 7], $stream->readCSVLine(","));


    }


}