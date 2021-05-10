<?php


namespace Kinintel\Services\Processing\Compression;

use Kinikit\Core\Stream\ReadableStream;

/**
 * Generic compressor interface
 *
 * @implementation zip Kinintel\Services\Processing\Compression\ZipCompressor
 */
interface Compressor {

    /**
     * Uncompress the passed stream using this compression format
     * and return a new stream to the uncompressed data
     *
     * @param ReadableStream $stream
     * @return ReadableStream
     */
    public function uncompress($stream);

}