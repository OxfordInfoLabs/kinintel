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
     * @var string
     */
    protected $description;

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
     * @param string $description
     */
    public function __construct($key, $title, $type, $description = null) {
        $this->title = $title;
        $this->key = $key;
        $this->type = $type;
        $this->description = $description;
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

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }


}