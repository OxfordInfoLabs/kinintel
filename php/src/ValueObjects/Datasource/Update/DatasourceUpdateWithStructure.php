<?php


namespace Kinintel\ValueObjects\Datasource\Update;


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
     * DatasourceUpdate constructor.
     *
     * @param string $title
     * @param string $importKey
     * @param DatasourceUpdateField[] $fields
     * @param mixed[] $adds
     * @param mixed[] $updates
     * @param mixed[] $deletes
     */
    public function __construct($title, $importKey = null, $fields = [], $adds = [], $updates = [], $deletes = []) {
        parent::__construct($adds, $updates, $deletes);
        $this->title = $title;
        $this->fields = $fields;
        $this->importKey = $importKey;
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


}
