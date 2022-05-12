<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Stream\StreamException;
use Kinikit\Core\Util\StringUtils;
use Kinintel\ValueObjects\Dataset\Field;

/**
 * General purpose tabular data set which receives a Separated Value stream
 * as input
 *
 * Class SVStreamTabularDataSet
 * @package Kinintel\Objects\Dataset\Tabular
 */
class SVStreamTabularDataSet extends TabularDataset {

    /**
     * @var ReadableStream
     */
    private $stream;

    /**
     * @var string
     */
    private $separator;

    /**
     * @var string
     */
    private $enclosure;


    /**
     * @var integer
     */
    private $limit;

    /**
     * @var int
     */
    private $readItems = 0;


    /**
     * CSV Columns as read from the stream
     *
     * @var Field[]
     */
    private $csvColumns = [];


    /**
     * An optional array of column indexes in the source CSV data to
     * ignore when mapping to columns.
     *
     * @var array
     */
    private $ignoreColumnIndexes = [];


    /**
     * If supplied as true, any blank column values will be skipped
     * and columns shifted to left.
     *
     * @var bool
     */
    private $skipBlankColumnValues = false;


    /**
     * SVStreamTabularDataSet constructor.
     *
     * @param Field[] $columns
     * @param ReadableStream $stream
     * @param int $firstRowOffset
     * @param false $firstRowHeader
     * @param string $separator
     * @param string $enclosure
     * @param array $ignoreColumnIndexes
     * @param integer $limit
     * @param integer $offset
     */
    public function __construct($columns, $stream, $firstRowOffset = 0, $firstRowHeader = false, $separator = ",", $enclosure = '"',
                                $limit = PHP_INT_MAX, $offset = 0, $ignoreColumnIndexes = [], $cacheAllRows = true, $skipBlankColumnValues = false) {
        parent::__construct($columns, $cacheAllRows);
        $this->stream = $stream;
        $this->separator = $separator;
        $this->enclosure = $enclosure;
        $this->ignoreColumnIndexes = $ignoreColumnIndexes;
        $this->skipBlankColumnValues = $skipBlankColumnValues;

        // Total limit including offset
        $this->limit = $limit + $offset + $firstRowOffset;


        // If first row offset supplied, move there now
        if ($firstRowOffset) {
            for ($i = 0; $i < $firstRowOffset; $i++)
                $this->nextRawDataItem();
        }

        // Read header row
        if ($firstRowHeader) {
            $this->readHeaderRow();
        } else {
            $this->csvColumns = $columns;
        }


        // if offset, forward wind to that offset
        if ($offset) {
            for ($i = 0; $i < $offset; $i++) {
                $this->nextRawDataItem();
            }
        }

    }


    /**
     * Fall back to the csv columns if no explicit columns
     *
     * @return Field[]
     */
    public function getColumns() {
        return sizeof($this->columns) ? $this->columns : $this->csvColumns;
    }

    /**
     * Ensure we close the stream on get all data
     *
     * @return mixed[]
     */
    public function getAllData() {
        $allData = parent::getAllData();
        $this->stream->close();
        return $allData;
    }


    /**
     * Read the next data item from the stream using the SV format.
     */
    public function nextRawDataItem() {

        // Shortcut if we have reached the limit
        if ($this->readItems >= $this->limit)
            return false;

        try {
            $csvLine = $this->stream->readCSVLine($this->separator, $this->enclosure);

            $this->readItems++;

            // Only continue if we have some content
            if (trim($csvLine[0])) {

                $dataItem = [];
                $columnIndex = 0;
                foreach ($csvLine as $index => $value) {
                    if (!in_array($index, $this->ignoreColumnIndexes) && (!$this->skipBlankColumnValues || trim($value))) {

                        // Ensure we have enough columns
                        if (!isset($this->csvColumns[$columnIndex]))
                            $this->csvColumns[] = new Field("column" . (sizeof($this->csvColumns) + 1));

                        if (isset($this->csvColumns[$columnIndex]))
                            $dataItem[$this->csvColumns[$columnIndex]->getName()] = trim($value);
                        $columnIndex++;
                    }
                }

                return $dataItem;
            } else {
                return null;
            }


        } catch (StreamException $e) {
            return false;
        }

    }


    /**
     * Return the stream, useful for testing
     *
     * @return ReadableStream
     */
    public function returnStream() {
        return $this->stream;
    }

    // Read the header row
    private function readHeaderRow() {

        // Grab the line as items
        $csvLine = $this->stream->readCSVLine($this->separator, $this->enclosure);

        // Create fields from these
        $columns = [];
        foreach ($csvLine as $column) {
            // Expand out the title and the name
            $name = StringUtils::convertToCamelCase(trim($column));
            $columns[] = new Field($name);
        }

        $this->csvColumns = $columns;
    }


}