<?php


namespace Kinintel\Objects\ResultFormatter;

use Exception;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Util\ArrayUtils;
use Kinikit\Core\Util\ObjectArrayUtils;
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
     * If defined the raw result will be added as a column field to each result
     *
     * @var string
     */
    private $rawResultFieldName;

    /**
     * For mapping a property of a parent into a child (see test for example)
     *
     * @var array
     */
    private $parentPropertyMappings;

    /**
     * JSONWebServiceResultMapping constructor.
     *
     * @param string $resultsOffsetPath
     * @param string $itemOffsetPath
     * @param bool $singleResult
     * @param string $rawResultFieldName
     */
    public function __construct($resultsOffsetPath = "",
                                $itemOffsetPath = "",
                                $singleResult = false,
                                $rawResultFieldName = null,
                                $parentPropertyMappings = []
    ) {
        $this->resultsOffsetPath = $resultsOffsetPath;
        $this->singleResult = $singleResult;
        $this->itemOffsetPath = $itemOffsetPath;
        $this->rawResultFieldName = $rawResultFieldName;
        $this->parentPropertyMappings = $parentPropertyMappings;
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
     * @return string
     */
    public function getRawResultFieldName() {
        return $this->rawResultFieldName;
    }

    /**
     * @param string $rawResultFieldName
     */
    public function setRawResultFieldName($rawResultFieldName) {
        $this->rawResultFieldName = $rawResultFieldName;
    }

    public function getParentPropertyMappings(): mixed {
        return $this->parentPropertyMappings;
    }


    /**
     * Map the result from the webservice to JSON using configured rules
     *
     * @param ReadableStream $stream
     * @param array $passedColumns
     * @param int $limit
     * @param int $offset
     * @return Dataset
     */
    public function format($stream, $passedColumns = [], $limit = PHP_INT_MAX, $offset = 0) {
        $columns = [];
        $data = [];

        // Grab full contents of this stream as incremental conversion is not supported
        $result = $stream->getContents();

        $originalDecodedResult = json_decode($result, true);
        $decodedResult = $originalDecodedResult;

        // Push down properties from parent to child, if necessary
        if ($this->parentPropertyMappings) {
            foreach ($this->parentPropertyMappings as $mapping => $mappedName){
                $this->insertMappedResult($decodedResult, $mapping, $mappedName, $this->getResultsOffsetPath());
            }
        }


        // if result path, drill down to here first
        if ($this->getResultsOffsetPath()) {
            $resultPath = $this->getResultsOffsetPath();
            $decodedResult = $this->drillDown($resultPath, $decodedResult);
        }

        // If a single result, convert to array for processing
        if (!is_array($decodedResult) || $this->isSingleResult()) {
            $decodedResult = [$decodedResult];
        } else {
            $decodedResult = array_values($decodedResult);
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

                if ($this->rawResultFieldName) {
                    $dataItem[$this->rawResultFieldName] = $originalDecodedResult;
                }

                $data[] = $dataItem;
            } else if (Primitive::isPrimitive($item)) {
                $columns = $this->ensureColumn("value", $columns);
                $data[] = ["value" => $item];
            }


        }

        // If we are capturing raw result for other processing, supply here
        if ($this->rawResultFieldName) {
            $columns = $this->ensureColumn($this->rawResultFieldName, $columns);
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
    public function drillDown(?string $path, $object) {
        if (!$path) {
            return $object;
        }

        $splitMapping = explode(".", $path, 2);
        [$nextSeg, $newPath] = match (count($splitMapping)) {
            0 => [null, null],
            1 => [$splitMapping[0], null],
            2 => [$splitMapping[0], $splitMapping[1]]
        };

        // JSON Array case
        if (array_key_exists(0, $object)){
            return array_merge(...array_map(fn($subobject) => $this->drillDown($path, $subobject), $object));
        }

        // Specific index case
        if (str_contains($nextSeg, "[")){
            $arrSplitPath = explode("[", $nextSeg);
            $key = $arrSplitPath[0];
            $idx = (int) explode("]", $arrSplitPath[1])[0];
            return $this->drillDown($newPath, $object[$key][$idx]);
        }

        if ($nextSeg && array_key_exists($nextSeg, $object)){
            return $this->drillDown($newPath, $object[$nextSeg]);
        } else {
            return null;
        }
    }

    public function insertMappedResult(
        array &$arrayToInsert,
        ?string $mapping,
        string $mappedName,
        ?string $resultsOffsetPath,
        $valueToMove = null) {

        // Case: JSON Array
        if (array_key_exists(0, $arrayToInsert)){
            return $arrayToInsert = array_map(
                fn($subarray) =>
                    $this->insertMappedResult($subarray, $mapping, $mappedName, $resultsOffsetPath, $valueToMove),
                $arrayToInsert);
        }

        if ($mapping){
            $splitMapping = explode(".", $mapping, 2);
            [$nextMappingSeg, $newMapping] = match (count($splitMapping)){
                0 => [null, null],
                1 => [$splitMapping[0], null],
                2 => [$splitMapping[0], $splitMapping[1]]
            };
        } else {
            [$nextMappingSeg, $newMapping] = [null, null];
        }

        if ($nextMappingSeg && !$newMapping){
            $valueToMove = $arrayToInsert[$mapping];
            $nextMappingSeg = null;
        }

        if ($resultsOffsetPath){
            $splitMapping = explode( ".", $resultsOffsetPath, 2);
            [$nextROPSeg, $newROP] = match (count($splitMapping)){
                0 => [null, null],
                1 => [$splitMapping[0], null],
                2 => [$splitMapping[0], $splitMapping[1]]
            };
        } else {
            $arrayToInsert[$mappedName] = $valueToMove;
            return $arrayToInsert;
        }

        match (true){
            array_key_exists($nextMappingSeg, $arrayToInsert) && $nextROPSeg == $nextMappingSeg
                => $this->insertMappedResult($arrayToInsert[$nextMappingSeg], $newMapping, $mappedName, $newROP, $valueToMove),

            array_key_exists($nextROPSeg, $arrayToInsert)
                => $this->insertMappedResult($arrayToInsert[$nextROPSeg], $newMapping, $mappedName, $newROP, $valueToMove),

            array_key_exists($nextMappingSeg, $arrayToInsert)
                => throw new Exception("Mapping must be hierarchically above resultsOffsetPath"),

            default
                => throw new Exception("Not implemented path. mapping: $mapping, ResultsOffsetPath: $resultsOffsetPath, nextROP: $nextROPSeg, ValueToMove: $valueToMove ||| ". print_r($arrayToInsert, true))
        };
        return $arrayToInsert;
    }


}
