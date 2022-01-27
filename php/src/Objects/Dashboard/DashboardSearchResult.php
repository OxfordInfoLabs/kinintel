<?php


namespace Kinintel\Objects\Dashboard;


use Kiniauth\Objects\MetaData\CategorySummary;
use Kinikit\Persistence\ORM\ActiveRecord;

class DashboardSearchResult extends ActiveRecord {

    /**
     * Primary key for this dashboard
     *
     * @var integer
     */
    protected $id;
    /**
     * Title for the dashboard
     *
     * @var string
     * @required
     */
    protected $title;


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
     * DashboardSearchResult constructor.
     * @param int $id
     * @param string $title
     */
    public function __construct($id, $title, $summary, $description, $categories) {
        $this->id = $id;
        $this->title = $title;
        $this->summary = $summary;
        $this->description = $description;
        $this->categories = $categories;
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
    public function getTitle() {
        return $this->title;
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

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }
}