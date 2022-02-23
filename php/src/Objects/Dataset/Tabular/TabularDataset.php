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
     * Historical read rows for all behaviour
     *
     * @var array
     */
    private $readRows = [];


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

        // Ensure all data has been read
        while ($this->nextDataItem()) {
        }

        // Combine any read rows into mix to ensure we can repeatedly call getAllData
        return array_merge($this->readRows);
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
            $hasColumnValue = false;
            foreach ($this->getColumns() as $column) {
                $columnName = $column->getName();

                if ($column->hasValueExpression()) {
                    $value = $column->evaluateValueExpression($dataItem);
                } else {
                    $value = isset($dataItem[$columnName]) ?? null;
                }

                $valueExpression = $column->getValueExpression();
                $hasColumnValue = $hasColumnValue
                    || (isset($value) && ((!$valueExpression) || is_numeric(strpos($valueExpression, "[["))));

                $newDataItem[$columnName] = $value;
            }

            // If no genuine column value reject the row
            if (!$hasColumnValue)
                $newDataItem = null;

        } else {
            $newDataItem = null;
        }

        // If a new data item, append to read rows
        if ($newDataItem) {
            $this->readRows[] = $newDataItem;
        }

        return $newDataItem;

    }


    /**
     * Return next raw data item for the dataset.
     */
    public abstract function nextRawDataItem();


}