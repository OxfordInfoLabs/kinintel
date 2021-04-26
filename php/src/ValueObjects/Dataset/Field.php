<?php


namespace Kinintel\ValueObjects\Dataset;


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
    public function __construct($name, $title) {
        $this->name = $name;
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