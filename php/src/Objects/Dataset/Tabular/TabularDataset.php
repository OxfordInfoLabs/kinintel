<?php


namespace Kinintel\Objects\Dataset\Tabular;


use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Dataset\Field;

/**
 * Classic tabular data set
 *
 */
abstract class TabularDataset implements Dataset {

    /**
     * @var Field[]
     */
    protected $columns;


    /**
     * Construct with columns - default behaviour
     *
     * TabularDataSetTest constructor.
     *
     * @param Field[] $columns
     */
    public function __construct($columns = []) {
        $this->columns = $columns;
    }

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


    /**
     * Return the columns as constructed
     *
     * @return Field[]
     */
    public function getColumns() {
        return $this->columns;
    }


    /**
     * Provide the next data item - this will be an associative array with keys matching
     * column names returned from getColumns
     *
     * @return mixed
     */
    public function nextDataItem() {

        // Grab data item
        $dataItem = $this->nextRawDataItem();

        if (is_array($dataItem)) {

            $newDataItem = [];
            foreach ($this->getColumns() as $column) {
                $columnName = $column->getName();

                if ($column->hasValueExpression()) {
                    $value = $column->evaluateValueExpression($dataItem);
                } else {
                    $value = $dataItem[$columnName] ?? null;
                }

                $newDataItem[$columnName] = $value;
            }
        } else {
            $newDataItem = null;
        }

        return $newDataItem;

    }


    /**
     * Return next raw data item for the dataset.
     */
    public abstract function nextRawDataItem();


}