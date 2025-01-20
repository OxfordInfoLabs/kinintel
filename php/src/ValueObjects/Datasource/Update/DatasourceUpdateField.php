<?php


namespace Kinintel\ValueObjects\Datasource\Update;


use Kinintel\ValueObjects\Dataset\Field;

class DatasourceUpdateField extends Field {

    /**
     * @var string
     */
    private $previousName;


    /**
     * @var
     */
    private $validationRules;


    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $title
     * @param string $valueExpression
     * @param string $type
     * @param boolean $keyField
     * @param string $previousName
     */
    public function __construct($name, $title = null, $valueExpression = null, $type = self::TYPE_STRING, $keyField = false, $previousName = "") {
        parent::__construct($name, $title, $valueExpression, $type, $keyField);
        $this->previousName = $previousName;
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


}