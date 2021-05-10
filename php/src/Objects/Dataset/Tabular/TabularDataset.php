<?php


namespace Kinintel\Objects\Dataset\Tabular;


use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Dataset\Field;

/**
 * Classic tabular data set
 *
 */
abstract class TabularDataset implements Dataset {

    /**
     * Array of fields representing the columns for this tabular set
     *
     * @var Field[]
     */
    private $columns;


    /**
     * TabularDataset constructor.  Base class only requires columns
     * as these are the only thing that's generic at the mo.  Subclasses should
     * supply other data as required.
     *
     * @param Field[] $columns
     */
    public function __construct($columns) {
        $this->columns = $columns;
    }


    /**
     * @return Field[]
     */
    public function getColumns() {
        return $this->columns;
    }


    /**
     * Set columns (internal use only)
     *
     * @param $columns
     */
    protected function setColumns($columns) {
        $this->columns = $columns;
    }

    /**
     * Provide the next data item
     */
    public abstract function nextDataItem();


    /**
     * By default, simply call next data item repeatedly until no more data available
     * and return
     *
     * @return mixed[]
     */
    public function getAllData() {
        $data = [];
        while ($row = $this->nextDataItem()) {
            $data[] = $row;
        }

        return $data;
    }


}