<?php


namespace Kinintel\Exception;


use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;

class InvalidDatasourceTypeException extends ValidationException {

    public function __construct($type) {
        parent::__construct(["dataSourceInstance" => [
            "type" => new FieldValidationError("type", "wrongtype", "The datasource type supplied '$type' does not exist")
        ]]);
    }
}