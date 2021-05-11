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
     * Get array of columns as Field objects
     *
     * @return Field[]
     */
    public abstract function getColumns();


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