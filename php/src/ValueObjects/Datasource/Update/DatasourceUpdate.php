<?php


namespace Kinintel\ValueObjects\Datasource\Update;

/**
 * Encapsulate a datasource update (adds, updates and deletes) designed for e.g. API use
 *
 * Class DatasourceUpdate
 * @package Kinintel\ValueObjects\Datasource
 */
class DatasourceUpdate {

    /**
     * @var mixed[]
     */
    private $adds;

    /**
     * @var mixed[]
     */
    private $updates;

    /**
     * @var mixed[]
     */
    private $deletes;


    /**
     * @var mixed[]
     */
    private $replaces;

    /**
     * DatasourceUpdate constructor.
     *
     * @param mixed[] $adds
     * @param mixed[] $updates
     * @param mixed[] $deletes
     * @param mixed[] $replaces
     */
    public function __construct($adds = [], $updates = [], $deletes = [], $replaces = []) {
        $this->adds = $adds;
        $this->updates = $updates;
        $this->deletes = $deletes;
        $this->replaces = $replaces;
    }

    /**
     * @return mixed[]
     */
    public function getAdds() {
        return $this->adds;
    }

    /**
     * @param mixed[] $adds
     */
    public function setAdds($adds) {
        $this->adds = $adds;
    }

    /**
     * @return mixed[]
     */
    public function getUpdates() {
        return $this->updates;
    }

    /**
     * @param mixed[] $updates
     */
    public function setUpdates($updates) {
        $this->updates = $updates;
    }

    /**
     * @return mixed[]
     */
    public function getDeletes() {
        return $this->deletes;
    }

    /**
     * @param mixed[] $deletes
     */
    public function setDeletes($deletes) {
        $this->deletes = $deletes;
    }

    /**
     * @return mixed[]
     */
    public function getReplaces() {
        return $this->replaces;
    }

    /**
     * @param mixed[] $replaces
     */
    public function setReplaces($replaces) {
        $this->replaces = $replaces;
    }


}