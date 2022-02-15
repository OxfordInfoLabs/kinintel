<?php


namespace Kinintel\ValueObjects\Datasource\Update;


use Kinintel\ValueObjects\Dataset\Field;

class DatasourceUpdateField extends Field {

    /**
     * @var string
     */
    private $originalName;


    /**
     * Field constructor.
     *
     * @param string $name
     * @param string $title
     * @param string $valueExpression
     * @param string $type
     * @param boolean $keyField
     */
    public function __construct($name, $title = null, $valueExpression = null, $type = self::TYPE_STRING, $keyField = false, $originalName) {
        parent::__construct($name, $title, $valueExpression, $type, $keyField);
        $this->originalName = $originalName;
    }


    /**
     * @return string
     */
    public function getOriginalName() {
        return $this->originalName;
    }

    /**
     * @param string $originalName
     */
    public function setOriginalName($originalName) {
        $this->originalName = $originalName;
    }


}