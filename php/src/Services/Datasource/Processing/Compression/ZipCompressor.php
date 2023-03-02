<?php


namespace Kinintel\Services\Datasource\Processing\Compression;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Exception\DatasourceCompressionException;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
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
    public function uncompress($stream, $config, $parameterValues = []) {

        // Create a folder to extract to
        $tmpDir = sys_get_temp_dir();
        $zipFile = tempnam($tmpDir, "zip");

        // Save the stream to a local file
        $zipFileResource = fopen($zipFile, "w");
        while (!$stream->isEof()) {
            if ($bytes = $stream->read(32768)) {
                fputs($zipFileResource, $bytes);
            }
        }
        fclose($zipFileResource);

        // Now open the file using zip
        $zip = new \ZipArchive();
        try {
            $opened = $zip->open($zipFile);
        } catch (\ErrorException $e) {
            $opened = false;
        }

        if ($opened !== TRUE) {
            throw new DatasourceCompressionException("The data stream for this data source is not in valid zip format");
        }

        mkdir($zipFile . "-extracted", 0777, true);

        $rawEntryFilenames = $config->getEntryFilenames() ?? [$config->getEntryFilename()];

        /**
         * @var ParameterisedStringEvaluator $parameterisedStringEvaluator
         */
        $parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);
        $entryFilenames = array_map(function ($filename) use ($parameterisedStringEvaluator, $parameterValues) {
            return $parameterisedStringEvaluator->evaluateString($filename, [], $parameterValues);
        }, $rawEntryFilenames);

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