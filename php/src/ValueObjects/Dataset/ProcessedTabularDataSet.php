<?php

namespace Kinintel\ValueObjects\Dataset;

class ProcessedTabularDataSet {

    /**
     * @var Field[]
     */
    private $columns;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param Field[] $columns
     * @param mixed $data
     */
    public function __construct($columns, $data) {
        $this->columns = $columns;
        $this->data = $data;
    }

    /**
     * @return Field[]
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }





}