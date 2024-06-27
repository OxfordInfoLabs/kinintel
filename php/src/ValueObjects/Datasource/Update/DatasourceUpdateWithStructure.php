<?php


namespace Kinintel\ValueObjects\Datasource\Update;


use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;

class DatasourceUpdateWithStructure extends DatasourceUpdate {

    /**
     * @var string
     */
    private $title;


    /**
     * @var string
     */
    private $importKey;


    /**
     * @var DatasourceUpdateField[]
     */
    private $fields;


    /**
     * @var Index[]
     */
    private $indexes;


    /**
     * DatasourceUpdate constructor.
     *
     * @param string $title
     * @param string $importKey
     * @param DatasourceUpdateField[] $fields
     * @param Index[] $indexes
     * @param mixed[] $adds
     * @param mixed[] $updates
     * @param mixed[] $deletes
     */
    public function __construct($title, $importKey = null, $fields = [], $indexes = [], $adds = [], $updates = [], $deletes = []) {
        parent::__construct($adds, $updates, $deletes);
        $this->title = $title;
        $this->fields = $fields;
        $this->importKey = $importKey;
        $this->indexes = $indexes;
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
    public function getImportKey() {
        return $this->importKey;
    }

    /**
     * @param string $importKey
     */
    public function setImportKey($importKey) {
        $this->importKey = $importKey;
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

    /**
     * @return Index[]
     */
    public function getIndexes() {
        return $this->indexes;
    }

    /**
     * @param Index[] $indexes
     */
    public function setIndexes($indexes) {
        $this->indexes = $indexes;
    }


}
