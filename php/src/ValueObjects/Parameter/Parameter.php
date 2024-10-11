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


    /**
     * A default value for this parameter if one has been supplied
     *
     * @var mixed
     */
    private $defaultValue;

    /**
     * Settings
     *
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    private $settings;

    const TYPE_TEXT = "text";
    const TYPE_NUMERIC = "numeric";
    const TYPE_DATE = "date";
    const TYPE_DATE_TIME = "datetime";
    const TYPE_BOOLEAN = "boolean";
    const TYPE_LIST = "list";

    /**
     * Parameter constructor.
     * @param string $name
     * @param string $title
     * @param string $type
     * @param bool $multiple
     * @param mixed $settings
     */
    public function __construct($name, $title, $type = self::TYPE_TEXT, $multiple = false, $defaultValue = null, $settings = null) {
        $this->name = $name;
        $this->title = $title;
        $this->type = $type;
        $this->multiple = $multiple;
        $this->defaultValue = $defaultValue;
        $this->settings = $settings;
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

    /**
     * @return mixed
     */
    public function getDefaultValue() {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue) {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return mixed
     */
    public function getSettings(): mixed {
        return $this->settings;
    }

    /**
     * @param mixed $settings
     * @return void
     */
    public function setSettings(mixed $settings): void {
        $this->settings = $settings;
    }

    /**
     * Validate a parameter value according to the type specified here.
     * Return true or false according to whether this matches the supplied type
     *
     * @param $value
     * @return boolean
     */
    public function validateParameterValue($value) {
        switch ($this->getType()) {
            case self::TYPE_TEXT:
                return is_string($value) || is_numeric($value) || is_bool($value);
            case self::TYPE_NUMERIC:
                return is_numeric($value);
            case self::TYPE_BOOLEAN:
                return is_bool($value) || $value === 0 || $value === 1;
            case self::TYPE_DATE:
                return date_create_from_format("Y-m-d", $value) ? true : false;
            case self::TYPE_DATE_TIME:
                return date_create_from_format("Y-m-d H:i:s", $value) ? true : false;
        }
    }


}
