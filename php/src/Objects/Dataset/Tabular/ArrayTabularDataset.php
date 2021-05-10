<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinintel\ValueObjects\Dataset\Field;

/**
 * Simple array tabular dataset
 *
 * Class ArrayTabularDataset
 * @package Kinintel\Objects\Dataset\Tabular
 */
class ArrayTabularDataset extends TabularDataset {

    /**
     * @var array
     */
    private $data;

    /**
     * @var integer
     */
    private $pointer = 0;


    /**
     * ArrayTabularDataset constructor.
     *
     * @param Field[] $columns
     * @param array $data
     */
    public function __construct($columns, $data) {
        parent::__construct($columns);
        $this->data = $data;
    }


    /**
     * Implement only required method
     */
    public function nextDataItem() {
        $item = $this->data[$this->pointer] ?? null;
        if ($item) $this->pointer++;
        return $item;
    }

    /**
     * Get all data
     *
     * @return array
     */
    public function getAllData() {
        return $this->data;
    }


}