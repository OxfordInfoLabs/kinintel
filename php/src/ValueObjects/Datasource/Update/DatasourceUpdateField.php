<?php


namespace Kinintel\ValueObjects\Datasource\Update;


use Kinintel\Objects\FieldValidator\DateFieldValidator;
use Kinintel\Objects\FieldValidator\FieldValidator;
use Kinintel\Objects\FieldValidator\NumericFieldValidator;
use Kinintel\ValueObjects\Dataset\Field;

class DatasourceUpdateField extends Field {

    /**
     * @var string
     */
    private $previousName;


    /**
     * @var DatasourceUpdateFieldValidatorConfig[]
     */
    private $validatorConfigs;


    /**
     * Temporal validators array
     *
     * @var FieldValidator[]
     */
    private $validators = null;

    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $title
     * @param string $valueExpression
     * @param string $type
     * @param boolean $keyField
     * @param string $previousName
     * @param DatasourceUpdateFieldValidatorConfig[] $validators
     */
    public function __construct($name, $title = null, $valueExpression = null, $type = self::TYPE_STRING, $keyField = false, $previousName = "", $validators = []) {
        parent::__construct($name, $title, $valueExpression, $type, $keyField);
        $this->previousName = $previousName;
        $this->validatorConfigs = $validators;
    }


    /**
     * @return string
     */
    public function getPreviousName() {
        return $this->previousName;
    }

    /**
     * @param string $previousName
     */
    public function setPreviousName($previousName) {
        $this->previousName = $previousName;
    }

    /**
     * @return DatasourceUpdateFieldValidatorConfig[]
     */
    public function getValidatorConfigs() {
        return $this->validatorConfigs;
    }

    /**
     * @param DatasourceUpdateFieldValidatorConfig[] $validatorConfigs
     */
    public function setValidatorConfigs($validatorConfigs) {
        $this->validatorConfigs = $validatorConfigs;
    }


    /**
     * Validate a value for this update field
     *
     * @param $value
     * @return bool|string
     */
    public function validateValue($value) {
        $validators = $this->getValidators();
        foreach ($validators as $validator) {
            $validatorResult = $validator->validateValue($value, $this);
            if (is_string($validatorResult))
                return $validatorResult;
        }
        return true;
    }


    // Ensure we have made the validators
    private function getValidators() {
        if ($this->validators === null) {
            $this->validators = [];

            // Add explicit validators first
            foreach ($this->validatorConfigs ?? [] as $validatorConfig) {
                $this->validators[] = $validatorConfig->returnFieldValidator();
            }

            // Add implicit validators
            switch ($this->getType()){
                case Field::TYPE_INTEGER:
                    $this->validators[] = new NumericFieldValidator(false);
                    break;
                case Field::TYPE_FLOAT:
                    $this->validators[] = new NumericFieldValidator(true);
                    break;
                case Field::TYPE_DATE:
                    $this->validators[] = new DateFieldValidator(false);
                    break;
                case Field::TYPE_DATE_TIME:
                    $this->validators[] = new DateFieldValidator(true);
                    break;
            }

        }
        return $this->validators;
    }
}