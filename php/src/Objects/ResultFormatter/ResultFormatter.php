<?php


namespace Kinintel\Objects\ResultFormatter;

// Result formatter interface
use Kinikit\Core\Stream\ReadableStream;
use Kinintel\Objects\Dataset\Dataset;


/**
 * Interface ResultFormatter
 *
 * @implementation jsonl Kinintel\Objects\ResultFormatter\JSONLResultFormatter
 * @implementation json Kinintel\Objects\ResultFormatter\JSONResultFormatter
 * @implementation sv Kinintel\Objects\ResultFormatter\SVResultFormatter
 * @implementation xml Kinintel\Objects\ResultFormatter\XMLResultFormatter
 *
 */
interface ResultFormatter {

    /**
     * Format the result from a datasource execution and return a dataset.
     *
     * @param ReadableStream $stream
     * @param array $columns
     * @param int $limit
     * @param int $offset
     * @return Dataset
     */
    public function format($stream, $columns = [], $limit = PHP_INT_MAX, $offset = 0);
}