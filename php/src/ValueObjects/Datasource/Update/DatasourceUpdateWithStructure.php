<?php


namespace Kinintel\ValueObjects\Datasource\Update;


use Kinintel\ValueObjects\Dataset\Field;

class DatasourceUpdateWithStructure extends DatasourceUpdate {

    /**
     * @var string
     */
    private $title;


    /**
     * @var DatasourceUpdateField[]
     */
    private $fields;


    /**
     * DatasourceUpdate constructor.
     *
     * @param string $title
     * @param DatasourceUpdateField[] $fields
     * @param mixed[] $adds
     * @param mixed[] $updates
     * @param mixed[] $deletes
     */
    public function __construct($title, $fields = [], $adds = [], $updates = [], $deletes = []) {
        parent::__construct($adds, $updates, $deletes);
        $this->title = $title;
        $this->fields = $fields;
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
     * @return DatasourceUpdateField[]
     */
    public function getFields() {
        return $this->fields;
    }

    /**
     * @param DatasourceUpdateField[] $fields
     */
    public function setFields($fields) {
        $this->fields = $fields;
    }


}