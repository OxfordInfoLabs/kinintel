<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase;

class Index {

    /**
     * @var string[]
     * @required
     */
    private $fieldNames;

    /**
     * @param string[] $fieldNames
     */
    public function __construct($fieldNames = []) {
        $this->fieldNames = $fieldNames;
    }


    /**
     * @return string[]
     */
    public function getFieldNames() {
        return $this->fieldNames;
    }

    /**
     * @param string[] $fieldNames
     */
    public function setFieldNames($fieldNames) {
        $this->fieldNames = $fieldNames;
    }


}