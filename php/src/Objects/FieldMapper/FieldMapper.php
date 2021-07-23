<?php


namespace Kinintel\Objects\FieldMapper;

/**
 * Field mapper - maps a source value to a target value using a rule.
 *
 * @implementation date Kinintel\Objects\FieldMapper\DateFieldMapper
 */
interface FieldMapper {

    /**
     * Only required method for a field mapper - maps a source value to a target value
     *
     * @param $sourceValue
     * @return mixed
     */
    public function mapValue($sourceValue);

}