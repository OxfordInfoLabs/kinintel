<?php


namespace Kinintel\Objects\Dataset;

/**
 * @table ki_dataset_instance
 */
class DatasetInstanceSearchResult {

    /**
     * @var integer
     */
    private $id;


    /**
     * @var string
     */
    private $title;

    /**
     * DatasetInstanceSearchResult constructor.
     *
     * @param int $id
     * @param string $title
     */
    public function __construct($id, $title) {
        $this->id = $id;
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
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


}