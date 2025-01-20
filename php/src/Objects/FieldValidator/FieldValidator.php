<?php

namespace Kinintel\Objects\FieldValidator;

use Google\Service\BlockchainNodeEngine\ValidatorConfig;

interface FieldValidator {

    /**
     * Validate a value for this rule.  Returns either
     * true if the value is valid or an error string if not valid.
     *
     * @param mixed $value
     * @param DatasourceUpdateField $field
     *
     * @return bool|string
     */
    public function validateValue($value, $field);

}