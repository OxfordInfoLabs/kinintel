<?php


namespace Kinintel\Services\Datasource\Processing\Compression;

use Kinikit\Core\Stream\ReadableStream;
use Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration\CompressorConfiguration;

/**
 * Generic compressor interface
 *
 * @implementation zip Kinintel\Services\Processing\Compression\ZipCompressor
 */
interface Compressor {


    /**
     * Get the config class in use for the compressor
     *
     * @return mixed
     */
    public function getConfigClass();

    /**
     * Uncompress the passed stream using this compression format
     * and return a new stream to the uncompressed data
     *
     * @param ReadableStream $stream
     * @param CompressorConfiguration$config
     * @return ReadableStream
     */
    public function uncompress($stream, $config);

}