<?php


namespace Kinintel\Objects\ResultFormatter;


use Kinintel\Objects\Dataset\Tabular\JSONLStreamTabularDataSet;
use Kinintel\ValueObjects\Dataset\Dataset;

class JSONLResultFormatter implements ResultFormatter {


    /**
     * First row offset (effectively skip some rows before starting reading).
     *
     * @var int
     */
    private $firstRowOffset = 0;


    /**
     * Offset path to an item if the JSON line has e.g. a wrapper property.
     *
     * @var string
     */
    private $itemOffsetPath;

    /**
     * JSONLResultFormatter constructor.
     *
     * @param string $itemOffsetPath
     */
    public function __construct($itemOffsetPath = null, $firstRowOffset = 0) {
        $this->itemOffsetPath = $itemOffsetPath;
        $this->firstRowOffset = $firstRowOffset;
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
     * Format a JSON Lines stream
     *
     * @param \Kinikit\Core\Stream\ReadableStream $stream
     * @param array $columns
     * @param int $limit
     * @param int $offset
     * @return Dataset|void
     */
    public function format($stream, $columns = [], $limit = PHP_INT_MAX, $offset = 0) {
        return new JSONLStreamTabularDataSet($columns, $stream, $this->firstRowOffset, $this->itemOffsetPath, $limit, $offset);
    }
}