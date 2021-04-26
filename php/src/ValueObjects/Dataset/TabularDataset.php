<?php


namespace Kinintel\ValueObjects\Dataset;


/**
 * Classic tabular data set
 *
 */
class TabularDataset implements Dataset {

    /**
     * Array of fields representing the columns for this tabular set
     *
     * @var Field[]
     */
    private $columns;


    /**
     * The data as a two dimensional array of rows containing column values
     * indexed by field names.
     *
     * @var mixed[]
     */
    private $data;

    /**
     * TabularDataset constructor.
     * @param Field[] $columns
     * @param mixed[] $data
     */
    public function __construct($columns, $data) {
        $this->columns = $columns;
        $this->data = $data;
    }


    /**
     * @return Field[]
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @return mixed[]
     */
    public function getData() {
        return $this->data;
    }


}