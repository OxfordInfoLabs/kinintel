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

        $entryFilenames = $config->getEntryFilenames() ?? [$config->getEntryFilename()];

        $result = $zip->extractTo($zipFile . "-extracted", $entryFilenames);

        if ($result === false) {
            $missing = [];
            foreach ($entryFilenames as $entryFilename) {
                if (!file_exists($zipFile . "-extracted/$entryFilename")) {
                    $missing[] = $entryFilename;
                }
            }
            throw new DatasourceCompressionException("The compressed zip data does not have entries for the following supplied entry filenames '" . join(",", $missing) . "'");
        }

        if (sizeof($entryFilenames) > 1) {
            $streamFilename = $zipFile . "-extracted/combined-stream";
            $catSources = [];
            foreach ($entryFilenames as $entryFilename) {
                $catSources[] = $zipFile . "-extracted/$entryFilename";
            }
            shell_exec("cat " . join(" ", $catSources) . " > $streamFilename");
        } else {
            $streamFilename = $zipFile . "-extracted/" . $config->getEntryFilename();
        }

        return new ReadOnlyFileStream($streamFilename);

    }


}