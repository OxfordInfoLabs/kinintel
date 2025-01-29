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
     * Queued items - used when array flattening is switched on for at least one field.
     *
     * @var array
     */
    private $queuedItems = null;


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
     * @param Field[] $columns
     */
    public function setColumns($columns) {
        $this->columns = $columns;
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

        // If we have queued items, use these next
        if ($this->queuedItems) {
            $newDataItem = array_shift($this->queuedItems);
        } else {

            // Grab data item
            $dataItem = $this->nextRawDataItem();

            // If false, quit now
            if ($dataItem === false)
                return false;


            // Initialise queued items
            $this->queuedItems = null;

            // The data should be rows of arrays
            if (!is_array($dataItem)) {
                return null;
            }

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


                // If flattening array, queue up other items
                if ($column->isFlattenArray() && is_array($value)) {
                    if (!is_array($this->queuedItems)) {
                        $this->queuedItems = [];
                    }

                    // Populate the queued items with values
                    for ($i = 1; $i < sizeof($value); $i++) {
                        if (!isset($this->queuedItems[$i])) $this->queuedItems[$i] = $newDataItem;
                        $this->queuedItems[$i][$columnName] = $value[$i];
                    }

                    // If a first value set this otherwise return null straightaway.
                    if (isset($value[0]))
                        $value = $value[0];
                    else
                        return null;

                } else {

                    // If we have a queued items array, set the value on these.
                    if (is_array($this->queuedItems)) {
                        foreach ($this->queuedItems as $index => $queuedItem) {
                            $this->queuedItems[$index][$columnName] = $value;
                        }
                    }
                }

                $newDataItem[$columnName] = $value;
                $dataItem[$columnName] = $value;


            }

            // If no genuine column value reject the row
            if (!$hasColumnValue)
                $newDataItem = null;

        }


        // If a new data item, append to read rows
        if ($newDataItem && $this->cacheAllRows) {
            $this->readRows[] = $newDataItem;
        }

        return $newDataItem;

    }


    /**
     * Read N items into an array
     *
     * @param $numberOfItems
     * @return array
     */
    public function nextNDataItems($numberOfItems) {
        $items = [];
        for ($i = 0; $i < $numberOfItems; $i++) {
            $row = $this->nextDataItem();
            if ($row !== false)
                $items[] = $row;
            else
                break;
        }

        return $items;
    }


    /**
     * Return next raw data item for the dataset.
     */
    public abstract function nextRawDataItem();


}