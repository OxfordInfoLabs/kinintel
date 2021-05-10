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
     * Field constructor.
     *
     * @param string $name
     * @param string $title
     */
    public function __construct($name, $title = null) {
        $this->name = $name;

        // If no title supplied, make one using the name
        if (!$title) {
            $title = StringUtils::convertFromCamelCase($name);
        }

        $this->title = $title;
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


}