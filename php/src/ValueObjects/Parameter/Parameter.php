<?php


namespace Kinintel\ValueObjects\Parameter;

/**
 * Parameters for use in datasets and dashboards as input and for additional filtering.
 *
 * Class Parameter
 * @package Kinintel\ValueObjects\Parameter
 */
class Parameter {

    /**
     * The name as used to reference this parameter
     *
     * @var string
     */
    private $name;


    /**
     * The title of this parameter as displayed to the end user
     *
     * @var string
     */
    private $title;


    /**
     * The type of parameter (for GUI purposes)
     *
     * @var string
     */
    private $type;


    /**
     * Whether or not this is a multiple parameter
     *
     * @var boolean
     */
    private $multiple;

    const TYPE_TEXT = "text";
    const TYPE_NUMERIC = "numeric";
    const TYPE_DATE = "date";
    const TYPE_BOOLEAN = "boolean";

    /**
     * Parameter constructor.
     * @param string $name
     * @param string $title
     * @param string $type
     * @param bool $multiple
     */
    public function __construct($name, $title, $type = self::TYPE_TEXT, $multiple = false) {
        $this->name = $name;
        $this->title = $title;
        $this->type = $type;
        $this->multiple = $multiple;
    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isMultiple() {
        return $this->multiple;
    }

    /**
     * @param bool $multiple
     */
    public function setMultiple($multiple) {
        $this->multiple = $multiple;
    }


}