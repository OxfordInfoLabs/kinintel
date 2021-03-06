<?php


namespace Kinintel\Objects\ResultFormatter;


use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\ValueObjects\Dataset\Field;

/**
 * Separated values result formatter
 *
 * Class SVResultFormatter
 *
 * @package Kinintel\Objects\ResultFormatter
 */
class SVResultFormatter implements ResultFormatter {

    /**
     * The field separator - defaults to ,
     *
     * @var string
     */
    private $separator;


    /**
     * The enclosure for quoted strings - defaults to "
     *
     * @var string
     */
    private $enclosure;

    /**
     * Offset to first row
     *
     * @var int
     */
    private $firstRowOffset;


    /**
     * Boolean as to whether the first row is a header
     *
     * @var boolean
     */
    private $firstRowHeader;

    /**
     * @var int[]
     */
    private $ignoreColumnIndexes;


    /**
     * @var boolean
     */
    private $skipBlankColumnValues;


    /**
     * SVResultFormatter constructor.
     *
     * @param string $separator
     * @param string $enclosure
     * @param int $firstRowOffset
     * @param boolean $firstRowHeader
     */
    public function __construct($separator = ",", $enclosure = '"', $firstRowOffset = 0, $firstRowHeader = false, $ignoreColumnIndexes = [], $skipBlankColumnValues = false) {
        $this->separator = $separator;
        $this->enclosure = $enclosure;
        $this->firstRowOffset = $firstRowOffset;
        $this->firstRowHeader = $firstRowHeader;
        $this->ignoreColumnIndexes = $ignoreColumnIndexes;
        $this->skipBlankColumnValues = $skipBlankColumnValues;
    }

    /**
     * @return string
     */
    public function getSeparator() {
        return $this->separator;
    }

    /**
     * @param string $separator
     */
    public function setSeparator($separator) {
        $this->separator = $separator;
    }


    /**
     * @return string
     */
    public function getEnclosure() {
        return $this->enclosure;
    }

    /**
     * @param string $enclosure
     */
    public function setEnclosure($enclosure) {
        $this->enclosure = $enclosure;
    }

    /**
     * @return int
     */
    public function getFirstRowOffset() {
        return $this->firstRowOffset;
    }

    /**
     * @param int $firstRowOffset
     */
    public function setFirstRowOffset($firstRowOffset) {
        $this->firstRowOffset = $firstRowOffset;
    }

    /**
     * @return bool
     */
    public function isFirstRowHeader() {
        return $this->firstRowHeader;
    }

    /**
     * @param bool $firstRowHeader
     */
    public function setFirstRowHeader($firstRowHeader) {
        $this->firstRowHeader = $firstRowHeader;
    }

    /**
     * @return int[]
     */
    public function getIgnoreColumnIndexes() {
        return $this->ignoreColumnIndexes;
    }

    /**
     * @param int[] $ignoreColumnIndexes
     */
    public function setIgnoreColumnIndexes($ignoreColumnIndexes): void {
        $this->ignoreColumnIndexes = $ignoreColumnIndexes;
    }


    /**
     * Format the value string
     *
     * @param ReadableStream $result
     * @param array $columns
     * @param int $limit
     * @param int $offset
     * @return Dataset
     */
    public function format($stream, $passedColumns = [], $limit = PHP_INT_MAX, $offset = 0) {
        return new SVStreamTabularDataSet($passedColumns, $stream, $this->firstRowOffset, $this->firstRowHeader, $this->separator, $this->enclosure, $limit, $offset, $this->ignoreColumnIndexes, true, $this->skipBlankColumnValues);
    }

}