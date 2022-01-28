<?php


namespace Kinintel\Objects\Dataset;

use Kiniauth\Objects\MetaData\CategorySummary;

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
     * Summary for the dashboard
     *
     * @var string
     */
    protected $summary;


    /**
     * Full HTML description for the dashboard
     *
     * @var string
     * @sqlType LONGTEXT
     */
    protected $description;

    /**
     * Array of category summary objects associated with this dashboard
     *
     * @var CategorySummary[]
     */
    protected $categories = [];


    /**
     * DatasetInstanceSearchResult constructor.
     *
     * @param int $id
     * @param string $title
     * @param string $summary
     * @param string $description
     * @param CategorySummary[] $categories
     */
    public function __construct($id, $title, $summary, $description, $categories) {
        $this->id = $id;
        $this->title = $title;
        $this->summary = $summary;
        $this->description = $description;
        $this->categories = $categories;
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

    /**
     * @return string
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * @param string $summary
     */
    public function setSummary($summary) {
        $this->summary = $summary;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return CategorySummary[]
     */
    public function getCategories() {
        return $this->categories;
    }

    /**
     * @param CategorySummary[] $categories
     */
    public function setCategories($categories) {
        $this->categories = $categories;
    }


}