<?php


namespace Kinintel\Exception;

class UnsupportedDatasetException extends \Exception {

    public function __construct($message) {
        parent::__construct($message);
    }

}