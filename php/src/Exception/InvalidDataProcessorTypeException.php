<?php


namespace Kinintel\Exception;

use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;

class InvalidDataProcessorTypeException extends ValidationException {

    public function __construct($type) {
        parent::__construct(["dataProcessorInstance" => [
            "type" => new FieldValidationError("type", "wrongtype", "The dataprocessor type supplied '$type' does not exist")
        ]]);
    }


}