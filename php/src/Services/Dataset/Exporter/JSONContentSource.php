<?php

namespace Kinintel\Services\Dataset\Exporter;

use Kinikit\MVC\ContentSource\StringContentSource;

class JSONContentSource extends StringContentSource {

    /**
     * Construct with object
     */
    public function __construct(private mixed $data) {
        parent::__construct(json_encode($data, JSON_INVALID_UTF8_IGNORE), "text/json");
    }


    /**
     * @return mixed
     */
    public function getData(): mixed {
        return $this->data;
    }


}