<?php


namespace Kinintel\ValueObjects\Transformation;


use Kinintel\ValueObjects\Dataset\Field;

interface SQLDatabaseTransformation extends Transformation {

    /**
     * Get the transformation processor key matching this database transformation
     *
     * @return string
     */
    public function getSQLTransformationProcessorKey();

    /**
     * @param Field[] $columns
     * @return Field[]
     */
    public function returnAlteredColumns(array $columns) : array;

}