<?php


namespace Kinintel\ValueObjects\Query\Filter;


class Filter {

    /**
     * Name of field to filter
     *
     * @var string
     */
    private $fieldName;


    /**
     * Filter value (depending on filter type)
     *
     * @var mixed
     */
    private $value;


    /**
     * Filter type - one of constants
     *
     * @var string
     */
    private $filterType;


    // Filter type constants (single valued)
    const FILTER_TYPE_EQUALS = "eq";
    const FILTER_TYPE_NOT_EQUALS = "neq";
    const FILTER_TYPE_NULL = "null";
    const FILTER_TYPE_NOT_NULL = "notnull";
    const FILTER_TYPE_GREATER_THAN = "gt";
    const FILTER_TYPE_LESS_THAN = "lt";
    const FILTER_TYPE_GREATER_OR_EQUAL_TO = "gte";
    const FILTER_TYPE_LESS_THAN_OR_EQUAL_TO = "lte";
    const FILTER_TYPE_LIKE = "like";

    // Filter type constants (multi valued)
    const FILTER_TYPE_BETWEEN = "between";
    const FILTER_TYPE_IN = "in";
    const FILTER_TYPE_NOT_ON = "notin";

    /**
     * Filter constructor.
     *
     * @param string $fieldName
     * @param mixed $value
     * @param string $filterType
     */
    public function __construct($fieldName, $value, $filterType) {
        $this->fieldName = $fieldName;
        $this->value = $value;
        $this->filterType = $filterType;
    }

    /**
     * @return string
     */
    public function getFieldName() {
        return $this->fieldName;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getFilterType() {
        return $this->filterType;
    }


}