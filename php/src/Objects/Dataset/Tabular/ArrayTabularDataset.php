<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinikit\Core\Logging\Logger;
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
    public function nextRawDataItem() {
        $item = $this->data[$this->pointer] ?? false;
        if ($item) $this->pointer++;
        return $item;
    }


}