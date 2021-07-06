<?php


namespace Kinintel\ValueObjects\Transformation\Columns;


use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Transformation;

class ColumnsTransformation implements Transformation {

    /**
     * @var Field[]
     */
    private $columns;

    /**
     * Columns constructor.
     *
     * @param Field[] $columns
     */
    public function __construct($columns = []) {
        $this->columns = $columns;
    }


    /**
     * @return Field[]
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @param Field[] $columns
     */
    public function setColumns($columns) {
        $this->columns = $columns;
    }


}