<?php


namespace Kinintel\Objects\Dataset\Tabular;


use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Stream\StreamException;
use Kinintel\ValueObjects\Dataset\Field;

class JSONLStreamTabularDataSet extends TabularDataset {


    /**
     * @var ReadableStream
     */
    private $stream;

    /**
     * First row offset if header rows before main results.
     *
     * @var int
     */
    private $firstRowOffset = 0;

    /**
     * Offset path to the item in each row (if unset assume whole row is the item).
     *
     * @var string
     */
    private $itemOffsetPath;


    /**
     * @var integer
     */
    private $limit = -1;

    /**
     * @var int
     */
    private $readItems = 0;

    /**
     * Temporary pending item stack
     *
     * @var array
     */
    private $pendingItemStack = [];


    /**
     * JSONLStreamTabularDataSet constructor.
     *
     * @param Field[] $columns
     * @param ReadableStream $stream
     * @param int $firstRowOffset
     * @param string $itemOffsetPath
     * @param int $limit
     * @param int $offset
     */
    public function __construct($columns, $stream, $firstRowOffset = 0, $itemOffsetPath = null, $limit = PHP_INT_MAX, $offset = 0) {
        $this->stream = $stream;
        $this->itemOffsetPath = $itemOffsetPath;

        // if we have a first row offset, skip to the point.
        for ($i = 0; $i < $firstRowOffset + $offset; $i++) {
            $this->nextRawDataItem();
        }

        $this->limit = $limit;

        // If no columns, create them from first item
        if (!$columns) {
            $columns = [];
            $nextItem = $this->nextRawDataItem();
            $this->pendingItemStack[] = $nextItem;
            if ($nextItem && is_array($nextItem)) {
                foreach (array_keys($nextItem) as $columnKey) {
                    $columns[] = new Field($columnKey);
                }
            }
        }

        parent::__construct($columns);

    }

    /**
     * Read the next raw data item
     *
     * @return mixed
     */
    public function nextRawDataItem() {

        if ($this->limit >= 0 && $this->readItems >= $this->limit)
            return false;

        // If we have queued up an item for return, return it first.
        if (sizeof($this->pendingItemStack)) {
            return array_shift($this->pendingItemStack);
        }

        try {
            $line = $this->stream->readLine();
            $lineObject = json_decode($line, true);
            if (is_array($lineObject)) {
                if ($this->itemOffsetPath) {
                    $lineObject = $this->drillDown($this->itemOffsetPath, $lineObject);
                }
                if ($this->limit >= 0)
                    $this->readItems++;
                return $lineObject;
            } else {
                if ($this->limit >= 0)
                    $this->readItems++;
                return null;
            }

        } catch (StreamException $e) {
            return false;
        }
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