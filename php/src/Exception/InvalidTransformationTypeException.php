<?php


namespace Kinintel\Exception;


use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;

class InvalidTransformationTypeException extends ValidationException {

    public function __construct($type) {
        parent::__construct(["transformationInstance" => [
            "type" => new FieldValidationError("type", "wrongtype", "The transformation type supplied '$type' does not exist")
        ]]);
    }
}