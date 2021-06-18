<?php


namespace Kinintel\Services\Datasource\Processing\Compression;


use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Exception\DatasourceCompressionException;
use Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration\ZipCompressorConfiguration;


/**
 * Compressor for the zip format
 *
 */
class ZipCompressor implements Compressor {

    public function getConfigClass() {
        return ZipCompressorConfiguration::class;
    }


    /**
     * Uncompress zip format file using readable stream.
     *
     * @param ReadableStream $stream
     * @param ZipCompressorConfiguration $config
     * @return ReadableStream
     */
    public function uncompress($stream, $config) {

        // Create a folder to extract to
        $tmpDir = sys_get_temp_dir();
        $zipFile = tempnam($tmpDir, "zip");

        file_put_contents($zipFile, $stream->getContents());

        // Now open the file using zip
        $zip = new \ZipArchive();
        $opened = $zip->open($zipFile);

        if ($opened !== TRUE) {
            throw new DatasourceCompressionException("The data stream for this data source is not in valid zip format");
        }

        mkdir($zipFile . "-extracted", 0777, true);

        $entry = $zip->extractTo($zipFile . "-extracted", $config->getEntryFilename());

        // If no entry matching entry filename throw compression exception
        if ($entry === false) {
            throw new DatasourceCompressionException("The compressed zip data does not have an entry matching the supplied entry filename '{$config->getEntryFilename()}'");
        }

        return new ReadOnlyFileStream($zipFile . "-extracted/" . $config->getEntryFilename());

    }


}