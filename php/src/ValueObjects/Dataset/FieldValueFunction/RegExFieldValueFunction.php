<?php


namespace Kinintel\ValueObjects\Dataset\FieldValueFunction;


class RegExFieldValueFunction implements FieldValueFunction {

    // We require an expression starting and ending with a /
    public function doesFunctionApply($functionString) {
        return substr($functionString, 0, 1) == "/" &&
            substr($functionString, -1, 1) == "/";
    }

    /**
     * Apply this function to the built expression and return an evaluated value
     *
     * @param string $functionString
     * @param string $value
     * @return string
     */
    public function applyFunction($functionString, $value, $dataItem) {
        preg_match($functionString, $value, $fieldMatches);
        return $fieldMatches[1] ?? $fieldMatches[0] ?? null;
    }
}