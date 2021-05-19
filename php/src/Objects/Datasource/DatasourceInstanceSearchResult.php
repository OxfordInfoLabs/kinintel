<?php


namespace Kinintel\Objects\Datasource;


use Kinikit\Persistence\ORM\ActiveRecord;

class DatasourceInstanceSearchResult extends ActiveRecord {
    /**
     * Descriptive title for this data source instance
     *
     * @var string
     * @required
     */
    protected $title;
    /**
     * @var string
     * @primaryKey
     */
    protected $key;

    /**
     * DatasourceInstanceSummary constructor.
     * @param string $title
     * @param string $key
     */
    public function __construct($key, $title) {
        $this->title = $title;
        $this->key = $key;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }
}