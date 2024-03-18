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
     * @var bool
     */
    private $runNow = false;

    /**
     * @param string $title
     * @param mixed[] $parameterValues
     * @param bool $runNow
     */
    public function __construct($title, array $parameterValues = [], $runNow = false) {
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
     * @return bool
     */
    public function isRunNow() {
        return $this->runNow;
    }


}