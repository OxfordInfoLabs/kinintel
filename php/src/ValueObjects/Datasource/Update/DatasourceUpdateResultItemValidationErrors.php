<?php

namespace Kinintel\ValueObjects\Datasource\Update;

class DatasourceUpdateResultItemValidationErrors {

    /**
     * Collection of validation errors which refer to an item.
     *
     * @param int $itemNumber
     * @param string[] $validationErrors
     */
    public function __construct(private int $itemNumber, private array $validationErrors = []) {
    }

    /**
     * @return int
     */
    public function getItemNumber(): int {
        return $this->itemNumber;
    }

    /**
     * @return string[]
     */
    public function getValidationErrors(): array {
        return $this->validationErrors;
    }


}