<?php


namespace Kinintel\ValueObjects\ImportExport;


class ImportItem {

    /**
     * @var string
     */
    private $title;


    /**
     * @var boolean
     */
    private $exists;

    /**
     * ImportItem constructor.
     *
     * @param string $title
     * @param boolean $exists
     */
    public function __construct($title, $exists = false) {
        $this->title = $title;
        $this->exists = $exists;
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
     * @return bool
     */
    public function isExists() {
        return $this->exists;
    }

    /**
     * @param bool $exists
     */
    public function setExists($exists) {
        $this->exists = $exists;
    }


}