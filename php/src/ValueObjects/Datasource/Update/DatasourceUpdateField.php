<?php


namespace Kinintel\ValueObjects\Datasource\Update;


use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\FieldValidator\BooleanFieldValidator;
use Kinintel\Objects\FieldValidator\DateFieldValidator;
use Kinintel\Objects\FieldValidator\FieldValidator;
use Kinintel\Objects\FieldValidator\NumericFieldValidator;
use Kinintel\Objects\FieldValidator\PickFromSourceFieldValidator;
use Kinintel\Objects\FieldValidator\RequiredFieldValidator;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
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
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $title
     * @param string $valueExpression
     * @param string $type
     * @param boolean $keyField
     * @param boolean $required
     * @param boolean $flattenArray
     * @param boolean $valueExpressionOnNullOnly
     * @param array $typeConfig
     * @param string $previousName
     * @param DatasourceUpdateFieldValidatorConfig[] $validators
     */
    public function __construct($name, $title = null, $valueExpression = null, $type = self::TYPE_STRING, $keyField = false, $required = false, $flattenArray = false, $valueExpressionOnNullOnly = false, $typeConfig = [],
                                $previousName = "", $validators = []) {
        parent::__construct($name, $title, $valueExpression, $type, $keyField, $required, $flattenArray, $valueExpressionOnNullOnly, $typeConfig);
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
     * testing only
     *
     * @param DatasourceService|object $datasourceService
     */
    public function setDatasourceService(?DatasourceService $datasourceService): void {
        $this->datasourceService = $datasourceService;
    }

    /**
     * testing only
     *
     * @param DatasetService|object $datasetService
     */
    public function setDatasetService(?DatasetService $datasetService): void {
        $this->datasetService = $datasetService;
    }


    /**
     * Validate a value for this update field
     *
     * @param $value
     * @return bool|string
     */
    public function validateValue($value) {

        $validators = $this->returnValidators();
        foreach ($validators as $validator) {
            $validatorResult = $validator->validateValue($value, $this);
            if (is_string($validatorResult))
                return $validatorResult;
        }
        return true;
    }


    // Ensure we have made the validators
    public function returnValidators() {
        if ($this->validators === null) {
            $this->validators = [];

            // Add explicit validators first
            foreach ($this->validatorConfigs ?? [] as $validatorConfig) {
                $this->validators[] = $validatorConfig->returnFieldValidator();
            }

            // Add implicit type validators
            switch ($this->getType()) {
                case Field::TYPE_INTEGER:
                    $fieldConfig = $this->returnFieldTypeConfig();
                    $this->validators[] = new NumericFieldValidator(false,
                        $fieldConfig?->getMinimumValue(), $fieldConfig?->getMaximumValue());
                    break;
                case Field::TYPE_FLOAT:
                    $fieldConfig = $this->returnFieldTypeConfig();
                    $this->validators[] = new NumericFieldValidator(true,
                        $fieldConfig?->getMinimumValue(), $fieldConfig?->getMaximumValue());
                    break;
                case Field::TYPE_BOOLEAN:
                    $this->validators[] = new BooleanFieldValidator();
                    break;
                case Field::TYPE_DATE:
                    $this->validators[] = new DateFieldValidator(false);
                    break;
                case Field::TYPE_DATE_TIME:
                    $this->validators[] = new DateFieldValidator(true);
                    break;
                case Field::TYPE_PICK_FROM_SOURCE:
                    $fieldConfig = $this->returnFieldTypeConfig();
                    $validator = new PickFromSourceFieldValidator($fieldConfig->getValueFieldName(),
                        $fieldConfig->getDatasetInstanceId(), $fieldConfig->getDatasourceInstanceKey());

                    if ($this->datasetService)
                        $validator->setDatasetService($this->datasetService);
                    if ($this->datasourceService)
                        $validator->setDatasourceService($this->datasourceService);

                    $this->validators[] = $validator;

            }

            // Add required validator if set
            if ($this->isRequired() || ($this->isKeyField() && ($this->getType() !== Field::TYPE_ID))) {
                $this->validators[] = new RequiredFieldValidator();
            }

        }
        return $this->validators;
    }


}