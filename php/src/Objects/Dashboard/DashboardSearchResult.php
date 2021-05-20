<?php


namespace Kinintel\Objects\Dashboard;


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
     * DashboardSearchResult constructor.
     * @param int $id
     * @param string $title
     */
    public function __construct($id, $title) {
        $this->id = $id;
        $this->title = $title;
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
     * @return int
     */
    public function getId() {
        return $this->id;
    }
}