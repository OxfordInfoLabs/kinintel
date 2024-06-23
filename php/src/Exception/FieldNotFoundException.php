<?php

namespace Kinintel\Exception;

use Kinikit\Core\Exception\ItemNotFoundException;

class FieldNotFoundException extends ItemNotFoundException {

    public function __construct($fieldName, $fieldLabel = "field", $operation = "") {
        parent::__construct("The $fieldLabel '$fieldName' used for $operation does not exist");
    }

}