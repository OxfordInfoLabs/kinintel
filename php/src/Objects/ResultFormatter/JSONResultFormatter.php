<?php


namespace Kinintel\Objects\ResultFormatter;

use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Util\Primitive;
use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\TabularDataset;

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
     * @return Dataset
     */
    public function format($stream) {

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

        return new TabularDataset(array_values($columns), $data);

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