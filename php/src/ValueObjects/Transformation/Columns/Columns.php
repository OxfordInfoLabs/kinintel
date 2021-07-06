<?php


namespace Kinintel\ValueObjects\Transformation\Columns;


use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Transformation;

class Columns implements Transformation {

    /**
     * @var Field[]
     */
    private $columns;

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