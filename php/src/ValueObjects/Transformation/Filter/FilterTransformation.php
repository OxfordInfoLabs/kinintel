<?php


namespace Kinintel\ValueObjects\Transformation\Filter;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

/**
 * Simple filter query
 *
 * @package Kinintel\Query
 */
class FilterTransformation extends FilterJunction implements Transformation, SQLDatabaseTransformation {

    /**
     * Get the transformation processor key for sql use
     *
     * @return string
     */
    public function getSQLTransformationProcessorKey() {
        return "filter";
    }
}