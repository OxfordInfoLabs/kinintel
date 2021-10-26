<?php


namespace Kinintel\Objects\ResultFormatter;

use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Util\Primitive;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\ValueObjects\Dataset\Field;

/**
 * Result mapping rules for JSON web service calls
 *
 * Class JSONWebServiceResultMapping
 * @package Kinintel\ValueObjects\Datasource\WebService
 */
class JSONResultFormatter implements ResultFormatter {

    /**
     * Path to the result data from top of JSON tree - defaults to blank (top of tree)
     *
     * @var string
     */
    private $resultsOffsetPath;


    /**
     * If set the result will be assumed to be a single result and not an array and will be
     * interpreted accordingly
     *
     * @var bool
     */
    private $singleResult;


    /**
     * @var string
     */
    private $itemOffsetPath;

    /**
     * JSONWebServiceResultMapping constructor.
     *
     * @param string $resultsOffsetPath
     * @param string $itemOffsetPath
     * @param bool $singleResult
     */
    public function __construct($resultsOffsetPath = "", $itemOffsetPath = "", $singleResult = false) {
        $this->resultsOffsetPath = $resultsOffsetPath;
        $this->singleResult = $singleResult;
        $this->itemOffsetPath = $itemOffsetPath;
    }


    /**
     * @return string
     */
    public function getResultsOffsetPath() {
        return $this->resultsOffsetPath;
    }

    /**
     * @param string $resultsOffsetPath
     */
    public function setResultsOffsetPath($resultsOffsetPath) {
        $this->resultsOffsetPath = $resultsOffsetPath;
    }

    /**
     * @return string
     */
    public function getItemOffsetPath() {
        return $this->itemOffsetPath;
    }

    /**
     * @param string $itemOffsetPath
     */
    public function setItemOffsetPath($itemOffsetPath) {
        $this->itemOffsetPath = $itemOffsetPath;
    }


    /**
     * @return bool
     */
    public function isSingleResult() {
        return $this->singleResult;
    }

    /**
     * @param bool $singleResult
     */
    public function setSingleResult($singleResult) {
        $this->singleResult = $singleResult;
    }


    /**
     * Map the result from the webservice to JSON using configured rules
     *
     * @param ReadableStream $stream
     * @param array $columns
     * @param int $limit
     * @param int $offset
     * @return Dataset
     */
    public function format($stream, $passedColumns = [], $limit = PHP_INT_MAX, $offset = 0) {

        $columns = [];
        $data = [];

        // Grab full contents of this stream as incremental conversion is not supported
        $result = $stream->getContents();

        $decodedResult = json_decode($result, true);

        // if result path, drill down to here first
        if ($this->getResultsOffsetPath()) {
            $resultPath = $this->getResultsOffsetPath();
            $decodedResult = $this->drillDown($resultPath, $decodedResult);
        }

        // If a single result, convert to array for processing
        if (!is_array($decodedResult) || $this->isSingleResult()) {
            $decodedResult = [$decodedResult];
        }

        // Deal with any offset and limit up front
        $decodedResult = array_slice($decodedResult, $offset, $limit);

        // Loop through each item
        foreach ($decodedResult as $item) {
            // If item is an array, we map accordingly
            if (is_array($item)) {

                // if an item offset path supplied, drill down.
                if ($this->getItemOffsetPath()) {
                    $item = $this->drillDown($this->getItemOffsetPath(), $item);
                }



                $dataItem = [];
                foreach ($item as $field => $value) {
                    $columnName = $field;
                    if (is_numeric($field)) {
                        $columnName = "value" . ($columnName + 1);
                    }
                    $columns = $this->ensureColumn($columnName, $columns);
                    $dataItem[$columnName] = $value;
                }
                $data[] = $dataItem;
            } else if (Primitive::isPrimitive($item)) {
                $columns = $this->ensureColumn("value", $columns);
                $data[] = ["value" => $item];
            }
        }

        return new ArrayTabularDataset(sizeof($passedColumns) ? $passedColumns : array_values($columns), $data);

    }

    // Ensure a column exists
    private function ensureColumn($columnName, $columns) {

        if (!isset($columns[$columnName])) {
            $columns[$columnName] = new Field($columnName);
        }

        return $columns;
    }


    // Drill down to a sub path in an object
    private function drillDown($path, $object) {

        $path = explode(".", $path);
        foreach ($path as $pathElement) {
            if (isset($object[$pathElement])) {
                $object = $object[$pathElement];
            } else {
                $object = [];
            }
        }

        return $object;
    }


}