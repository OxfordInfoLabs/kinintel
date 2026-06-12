<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinintel\Exception\UnsupportedDatasetException;
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
     * @param array|null $data An array of rows of data
     */
    public function __construct($columns, ?array $data) {
        parent::__construct($columns);

        if ($data && (!array_key_exists(0, $data) || (isset($data[0]) && !is_array($data[0])))) {
            throw new UnsupportedDatasetException("ArrayTabularDataset expects an array of arrays!!");
        }
        $this->data = $data;
    }


    /**
     * Implement only required method
     *
     * @return array|false
     */
    public function nextRawDataItem() {
        return sizeof($this->data) ? array_shift($this->data) : false;
    }


}