<?php


namespace Kinintel\ValueObjects\Transformation\Query;

use Kinintel\ValueObjects\Transformation\Query\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;

/**
 * Simple filter query
 *
 * @package Kinintel\Query
 */
class FilterQuery extends FilterJunction implements Query, SQLDatabaseTransformation {

    /**
     * Get the transformation processor key for sql use
     *
     * @return string
     */
    public function getSQLTransformationProcessorKey() {
        return "filterquery";
    }
}