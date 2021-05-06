<?php


namespace Kinintel\ValueObjects\Datasource\ResultFormatter;

// Result formatter interface
use Kinintel\ValueObjects\Dataset\Dataset;

/**
 * Interface ResultFormatter
 *
 * @implementation json Kinintel\ValueObjects\Datasource\ResultFormatter\JSONResultFormatter
 *
 */
interface ResultFormatter {

    /**
     * Format the result from a datasource execution and return a dataset.
     *
     * @param mixed $result
     * @return Dataset
     */
    public function format($result);
}