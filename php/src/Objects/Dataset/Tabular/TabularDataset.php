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
     * @var Field[]
     */
    protected $columns;


    /**
     * Boolean indicator as to whether we want to cache all rows - defaults to true
     *
     * @var boolean
     */
    private $cacheAllRows;


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
    public function __construct($columns = [], $cacheAllRows = true) {
        $this->columns = $columns;
        $this->cacheAllRows = $cacheAllRows;
    }

    /**
     * By default, simply call next data item repeatedly until no more data available
     * and return
     *
     * @return mixed[]
     */
    public function getAllData() {

        // Ensure all data has been read
        while ($this->nextDataItem() !== false) {
        }

        // Return read rows
        return $this->readRows;
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
     * Return a column by key
     *
     * @return Field
     */
    public function getColumnByName($name) {
        foreach ($this->getColumns() ?? [] as $column) {
            if ($column->getName() == $name)
                return $column;
        }
        return null;
    }

    /**
     * Disable row data caching - used programmatically when we know a dataset
     * will only be read once.
     */
    public function disableRowDataCaching() {
        $this->cacheAllRows = false;
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

        // If false, quit now
        if ($dataItem === false)
            return false;

        if (is_array($dataItem)) {

            $newDataItem = [];
            $hasColumnValue = false;
            foreach ($this->getColumns() as $column) {
                $columnName = $column->getName();

                if ($column->hasValueExpression()) {
                    $value = $column->evaluateValueExpression($dataItem);
                } else {
                    $value = $dataItem[$columnName] ?? null;
                }


                $valueExpression = $column->getValueExpression();

                $hasColumnValue = $hasColumnValue
                    || (isset($value) && ((!$valueExpression) || is_numeric(strpos($valueExpression, "[["))));

                $newDataItem[$columnName] = $value;
                $dataItem[$columnName] = $value;
            }

            // If no genuine column value reject the row
            if (!$hasColumnValue)
                $newDataItem = null;

        } else {
            $newDataItem = null;
        }

        // If a new data item, append to read rows
        if ($newDataItem && $this->cacheAllRows) {
            $this->readRows[] = $newDataItem;
        }

        return $newDataItem;

    }


    /**
     * Return next raw data item for the dataset.
     */
    public abstract function nextRawDataItem();


}