<?php


namespace Kinintel\ValueObjects\Dataset;


use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\StringUtils;

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


    // Generic field types
    const TYPE_STRING = "string";
    const TYPE_INTEGER = "integer";
    const TYPE_FLOAT = "float";
    const TYPE_DATE = "date";
    const TYPE_DATE_TIME = "datetime";

    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $title
     * @param string $valueExpression
     * @param string $type
     */
    public function __construct($name, $title = null, $valueExpression = null, $type = self::TYPE_STRING) {
        $this->name = $name;

        // If no title supplied, make one using the name
        if (!$title) {
            $title = StringUtils::convertFromCamelCase($name);
        }

        $this->title = $title;
        $this->valueExpression = $valueExpression;
        $this->type = $type;
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
     * Evaluate the value expression defined using a supplied data item
     *
     * @param $dataItem
     */
    public function evaluateValueExpression($dataItem) {
        $expression = $this->valueExpression;
        $expression = preg_replace_callback("/\\[\\[(.*?)(:.*?)*\\]\\]/", function ($matches) use ($dataItem) {
            if (sizeof($matches) == 2) {
                return $this->expandMemberExpression($matches[1], $dataItem);
            } else if (sizeof($matches) == 3) {
                preg_match(substr($matches[2], 1), $this->expandMemberExpression($matches[1], $dataItem), $fieldMatches);
                return $fieldMatches[1] ?? $fieldMatches[0] ?? null;
            }
        }, $expression);
        return $expression !== "" ? $expression : null;
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