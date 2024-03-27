<?php

namespace Kinintel\ValueObjects\DataProcessor\Snapshot;

class SnapshotDescriptor {

    /**
     * @var string
     * @required
     */
    private $title;

    /**
     * @var mixed[]
     */
    private $parameterValues;


    /**
     * @var string[][]
     */
    private $indexes;


    /**
     * @var bool
     */
    private $runNow = true;

    /**
     * @param string $title
     * @param mixed[] $parameterValues
     * @param string[][] $indexes
     * @param bool $runNow
     */
    public function __construct($title, array $parameterValues = [], array $indexes = [], $runNow = true) {
        $this->title = $title;
        $this->parameterValues = $parameterValues;
        $this->runNow = $runNow;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return mixed[]
     */
    public function getParameterValues() {
        return $this->parameterValues;
    }

    /**
     * @return string[][]
     */
    public function getIndexes() {
        return $this->indexes;
    }

    /**
     * @return bool
     */
    public function isRunNow() {
        return $this->runNow;
    }


}