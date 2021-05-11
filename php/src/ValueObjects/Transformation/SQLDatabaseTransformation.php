<?php


namespace Kinintel\ValueObjects\Transformation;


interface SQLDatabaseTransformation extends Transformation {

    /**
     * Get the transformation processor key matching this database transformation
     *
     * @return string
     */
    public function getSQLTransformationProcessorKey();

}