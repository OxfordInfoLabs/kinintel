<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\WebScraper;

use Kinintel\ValueObjects\Dataset\Field;

class FieldWithXPathSelector extends Field {

    /**
     * @var string
     */
    private $xpath;

    /**
     * @var string
     */
    private $attribute;


    // Attribute
    const ATTRIBUTE_TEXT = "TEXT";
    const ATTRIBUTE_HTML = "HTML";


    public function __construct($name, $xpath = null, $attribute = null, $title = null, $valueExpression = null, $type = self::TYPE_STRING, $keyField = false, $flattenArray = false) {
        parent::__construct($name, $title, $valueExpression, $type, $keyField, $flattenArray);
        $this->xpath = $xpath;
        $this->attribute = $attribute;
    }

    public function asField() {
        return new Field(
            $this->getName(),
            $this->getTitle(),
            $this->getValueExpression(),
            $this->getType(),
            $this->isKeyField(),
            $this->isFlattenArray(),
        );
    }


    /**
     * @return string
     */
    public function getXpath() {
        return $this->xpath;
    }

    /**
     * @param string $xpath
     */
    public function setXpath($xpath) {
        $this->xpath = $xpath;
    }

    /**
     * @return string
     */
    public function getAttribute() {
        return $this->attribute;
    }

    /**
     * @param string $attribute
     */
    public function setAttribute($attribute) {
        $this->attribute = $attribute;
    }


}