<?php


namespace Kinintel\Objects\ResultFormatter;

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
    private $resultPath;


    /**
     * If set the result will be assumed to be a single result and not an array and will be
     * interpreted accordingly
     *
     * @var bool
     */
    private $singleResult;

    /**
     * JSONWebServiceResultMapping constructor.
     *
     * @param string $resultPath
     * @param bool $singleResult
     */
    public function __construct($resultPath = "", $singleResult = false) {
        $this->resultPath = $resultPath;
        $this->singleResult = $singleResult;
    }


    /**
     * @return string
     */
    public function getResultPath() {
        return $this->resultPath;
    }

    /**
     * @param string $resultPath
     */
    public function setResultPath($resultPath) {
        $this->resultPath = $resultPath;
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
     * @param int $limit
     * @param int $offset
     * @return Dataset
     */
    public function format($stream, $limit = PHP_INT_MAX, $offset = 0) {

        $columns = [];
        $data = [];

        // Grab full contents of this stream as incremental conversion is not supported
        $result = $stream->getContents();

        $decodedResult = json_decode($result, true);

        // if result path, drill down to here first
        if ($this->getResultPath()) {
            $resultPath = $this->getResultPath();
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


        return new ArrayTabularDataset(array_values($columns), $data);

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