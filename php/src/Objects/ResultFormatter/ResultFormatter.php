<?php


namespace Kinintel\Objects\ResultFormatter;

// Result formatter interface
use Kinikit\Core\Stream\ReadableStream;
use Kinintel\ValueObjects\Dataset\Dataset;

/**
 * Interface ResultFormatter
 *
 * @implementation json Kinintel\Objects\ResultFormatter\JSONResultFormatter
 * @implementation sv Kinintel\Objects\ResultFormatter\SVResultFormatter
 *
 */
interface ResultFormatter {

    /**
     * Format the result from a datasource execution and return a dataset.
     *
     * @param ReadableStream $stream
     * @param int $limit
     * @param int $offset
     * @return Dataset
     */
    public function format($stream, $limit = PHP_INT_MAX, $offset = 0);
}