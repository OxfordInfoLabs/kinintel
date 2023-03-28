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
    public function nextRawDataItem() {
        return sizeof($this->data) ? array_shift($this->data) : false;
    }


}