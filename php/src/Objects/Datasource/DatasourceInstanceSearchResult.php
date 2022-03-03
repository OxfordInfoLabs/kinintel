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
     * Type for this data source - can either be a mapping implementation key
     * or a fully qualified class path
     *
     * @var string
     */
    protected $type;

    /**
     * DatasourceInstanceSummary constructor.
     * @param string $title
     * @param string $key
     * @param string $type
     */
    public function __construct($key, $title, $type) {
        $this->title = $title;
        $this->key = $key;
        $this->type = $type;
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

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }
}