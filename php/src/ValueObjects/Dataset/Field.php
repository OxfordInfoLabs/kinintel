<?php


namespace Kinintel\ValueObjects\Dataset;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\ValueFunction\ValueFunctionEvaluator;
use Kinikit\Core\Util\StringUtils;
use Kinintel\ValueObjects\Dataset\TypeConfig\FieldTypeConfig;

class Field {

    /**
     * Name for this field
     *
     * @var string
     */
    private $name;

    /**
     * Friendly name for this field
     *
     * @var string
     */
    private $title;


    /**
     * A static value for this field
     *
     * @var mixed
     */
    private $valueExpression;


    /**
     * Boolean flag which if set will only
     * evaluate the value expression if the data value
     * for this field is null.  This is useful in persistence scenarios.
     *
     * @var boolean
     */
    private $valueExpressionOnNullOnly;

    /**
     * @var string
     */
    private $type;


    /**
     * @var mixed
     */
    private $typeConfig;


    /**
     * @var boolean
     */
    private $keyField;


    /**
     * @var boolean
     */
    private $required;


    /**
     * If set, when an array is encountered here it will be flattened
     * out to individual items
     *
     * @var bool
     */
    private $flattenArray = false;


    // Generic field types
    const TYPE_STRING = "string";
    const TYPE_MEDIUM_STRING = "mediumstring";
    const TYPE_LONG_STRING = "longstring";
    const TYPE_INTEGER = "integer";
    const TYPE_FLOAT = "float";
    const TYPE_BOOLEAN = "boolean";
    const TYPE_DATE = "date";
    const TYPE_DATE_TIME = "datetime";
    const TYPE_PICK_FROM_SOURCE = "pickfromsource";
    const TYPE_ID = "id";
    const TYPE_JSON = "json";


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
     */
    public function __construct($name, $title = null, $valueExpression = null, $type = self::TYPE_STRING, $keyField = false, $required = false, $flattenArray = false,
                                $valueExpressionOnNullOnly = false, $typeConfig = []) {

        $name = preg_split("/[^\w-]/", $name)[0];
        $this->name = preg_replace("/[^a-zA-Z0-9\-_]/", "", $name);

        // If no title supplied, make one using the name
        if (!$title) {
            $title = StringUtils::convertFromCamelCase($name);
        }

        $this->title = $title;
        $this->valueExpression = $valueExpression;
        $this->type = $type;
        $this->keyField = $keyField;
        $this->flattenArray = $flattenArray;
        $this->valueExpressionOnNullOnly = $valueExpressionOnNullOnly;
        $this->typeConfig = $typeConfig;
        $this->required = $required;
    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getValueExpression() {
        return $this->valueExpression;
    }

    /**
     * @param mixed $valueExpression
     */
    public function setValueExpression($valueExpression) {
        $this->valueExpression = $valueExpression;
    }


    /**
     * @return mixed
     */
    public function hasValueExpression() {
        return $this->valueExpression ? true : false;
    }

    /**
     * @return bool
     */
    public function isValueExpressionOnNullOnly(): bool {
        return $this->valueExpressionOnNullOnly;
    }

    /**
     * @param bool $valueExpressionOnNullOnly
     */
    public function setValueExpressionOnNullOnly(bool $valueExpressionOnNullOnly): void {
        $this->valueExpressionOnNullOnly = $valueExpressionOnNullOnly;
    }


    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getTypeConfig() {
        return $this->typeConfig;
    }

    /**
     * @param mixed $typeConfig
     */
    public function setTypeConfig($typeConfig) {
        $this->typeConfig = $typeConfig;
    }


    /**
     * @return boolean
     */
    public function isKeyField() {
        return $this->keyField;
    }

    /**
     * @param boolean $keyField
     */
    public function setKeyField($keyField) {
        $this->keyField = $keyField;
    }

    /**
     * @return bool
     */
    public function isRequired() {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired($required) {
        $this->required = $required;
    }


    /**
     * @return bool
     */
    public function isFlattenArray() {
        return $this->flattenArray;
    }

    /**
     * @param bool $flattenArray
     */
    public function setFlattenArray($flattenArray) {
        $this->flattenArray = $flattenArray;
    }


    /**
     * Return the field type config as config object if defined.
     *
     * @return FieldTypeConfig
     */
    public function returnFieldTypeConfig() {
        $configClass = Container::instance()->getInterfaceImplementationClass(FieldTypeConfig::class, $this->getType());
        if ($configClass) {
            if (is_a($this->getTypeConfig(), $configClass))
                return $this->getTypeConfig();
            $objectBinder = Container::instance()->get(ObjectBinder::class);
            return $objectBinder->bindFromArray($this->getTypeConfig() ?? [], $configClass);
        }
        return null;
    }

    /**
     * Evaluate the value expression defined using a supplied data item
     *
     * @param $dataItem
     */
    public function evaluateValueExpression($dataItem) {
        $expression = $this->valueExpression;

        $fieldValue = $dataItem[$this->name] ?? null;

        if (!$this->isValueExpressionOnNullOnly() || ($fieldValue == null)) {
            $valueFunctionEvaluator = Container::instance()->get(ValueFunctionEvaluator::class);
            return $valueFunctionEvaluator->evaluateString($expression, $dataItem, ["[[", "]]"]);
        } else {
            return $fieldValue;
        }
    }


    /**
     * Convert an array of fields to plain fields (remove any value expression)
     *
     * @param Field[] $fields
     */
    public static function toPlainFields($fields, $removeKeys = false) {
        return array_map(function ($field) use ($removeKeys) {
            return new Field($field->getName(), $field->getTitle(), null, $field->getType(), !$removeKeys && $field->isKeyField());
        }, $fields);
    }


    // Expand member expression
    private function expandMemberExpression($expression, $dataItem) {

        $explodedExpression = explode(".", $expression);
        foreach ($explodedExpression as $expression) {
            $dataItem = $dataItem[$expression] ?? null;
        }
        return $dataItem;
    }


}
