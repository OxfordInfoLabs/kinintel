<?php


namespace Kinintel\ValueObjects\Dataset;


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
     * @var mixed
     */
    private $staticValue;


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
     */
    public function __construct($name, $title = null, $staticValue = null, $type = self::TYPE_STRING) {
        $this->name = $name;

        // If no title supplied, make one using the name
        if (!$title) {
            $title = StringUtils::convertFromCamelCase($name);
        }

        $this->title = $title;
        $this->staticValue = $staticValue;
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
    public function getStaticValue() {
        return $this->staticValue;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }


}