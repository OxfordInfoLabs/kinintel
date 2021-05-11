<?php


namespace Kinintel\Services\Processing\Compression;


use Kinikit\Core\Stream\ReadableStream;

/**
 * Compressor for the zip format
 *
 */
class ZipCompressor implements Compressor {

    /**
     * Uncompress zip format file using readable stream.
     *
     * @param ReadableStream $stream
     * @return ReadableStream|void
     */
    public function uncompress($stream) {

    }
}