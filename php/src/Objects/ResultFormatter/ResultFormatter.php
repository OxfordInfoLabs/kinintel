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
     * @return Dataset
     */
    public function format($stream);
}