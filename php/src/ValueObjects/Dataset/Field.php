<?php


namespace Kinintel\ValueObjects\Dataset;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\StringUtils;
use Kinintel\Services\Dataset\FieldValueFunctionEvaluator;

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
     * @var string
     */
    private $type;


    /**
     * @var boolean
     */
    private $keyField;


    // Generic field types
    const TYPE_STRING = "string";
    const TYPE_INTEGER = "integer";
    const TYPE_FLOAT = "float";
    const TYPE_DATE = "date";
    const TYPE_DATE_TIME = "datetime";
    const TYPE_ID = "id";

    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $title
     * @param string $valueExpression
     * @param string $type
     * @param boolean $keyField
     */
    public function __construct($name, $title = null, $valueExpression = null, $type = self::TYPE_STRING, $keyField = false) {
        $this->name = $name;

        // If no title supplied, make one using the name
        if (!$title) {
            $title = StringUtils::convertFromCamelCase($name);
        }

        $this->title = $title;
        $this->valueExpression = $valueExpression;
        $this->type = $type;
        $this->keyField = $keyField;
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
     * @return mixed
     */
    public function hasValueExpression() {
        return $this->valueExpression ? true : false;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
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
     * Evaluate the value expression defined using a supplied data item
     *
     * @param $dataItem
     */
    public function evaluateValueExpression($dataItem) {
        $expression = $this->valueExpression;

        $expression = preg_replace_callback("/\\[\\[(.*?)\\]\\]/", function ($matches) use ($dataItem) {

            $exploded = explode(" | ", $matches[1]);

            // Initialise value to member expression
            $value = $this->expandMemberExpression($exploded[0], $dataItem);

            if (sizeof($exploded) > 1) {
                $evaluator = Container::instance()->get(FieldValueFunctionEvaluator::class);
                for ($i = 1; $i < sizeof($exploded); $i++) {
                    $value = $evaluator->evaluateFieldValueFunction(trim($exploded[$i]), $value, $dataItem);
                }
            }

            return $value;

        }, $expression);
        return $expression !== "" ? $expression : null;
    }


    /**
     * Convert an array of fields to plain fields (remove any value expression)
     *
     * @param Field[] $fields
     */
    public static function toPlainFields($fields) {
        return array_map(function ($field) {
            return new Field($field->getName(), $field->getTitle(), null, $field->getType(), $field->isKeyField());
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