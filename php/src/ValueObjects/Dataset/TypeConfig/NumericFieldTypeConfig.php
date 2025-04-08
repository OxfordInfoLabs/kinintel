<?php

namespace Kinintel\ValueObjects\Dataset\TypeConfig;

class NumericFieldTypeConfig implements FieldTypeConfig {

    /**
     * Config for numeric fields
     *
     * @param ?float $minimumValue
     * @param ?float $maximumValue
     */
    public function __construct(private ?float $minimumValue = null, private ?float $maximumValue = null) {
    }

    /**
     * @return ?float
     */
    public function getMinimumValue(): ?float {
        return $this->minimumValue;
    }

    /**
     * @param ?float $minimumValue
     */
    public function setMinimumValue(?float $minimumValue): void {
        $this->minimumValue = $minimumValue;
    }

    /**
     * @return ?float
     */
    public function getMaximumValue(): ?float {
        return $this->maximumValue;
    }

    /**
     * @param ?float $maximumValue
     */
    public function setMaximumValue(?float $maximumValue): void {
        $this->maximumValue = $maximumValue;
    }


}