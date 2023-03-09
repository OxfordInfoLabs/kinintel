<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\Multi;


use Kinintel\Objects\DataProcessor\DataProcessorInstance;

class MultiDataProcessorConfiguration {

    /**
     * @var string[]
     * @requiredEither dataProcessors
     */
    private $dataProcessorKeys;

    /**
     * @var DataProcessorInstance[]
     */
    private $dataProcessors;

    /**
     * @param string[] $dataProcessorKeys
     * @param DataProcessorInstance[] $dataProcessors
     */
    public function __construct($dataProcessorKeys = [], $dataProcessors = []) {
        $this->dataProcessorKeys = $dataProcessorKeys;
        $this->dataProcessors = $dataProcessors;
    }

    /**
     * @return string[]
     */
    public function getDataProcessorKeys() {
        return $this->dataProcessorKeys;
    }

    /**
     * @param string[] $dataProcessorKeys
     */
    public function setDataProcessorKeys($dataProcessorKeys) {
        $this->dataProcessorKeys = $dataProcessorKeys;
    }

    /**
     * @return DataProcessorInstance[]
     */
    public function getDataProcessors() {
        return $this->dataProcessors;
    }

    /**
     * @param DataProcessorInstance[] $dataProcessors
     */
    public function setDataProcessors($dataProcessors) {
        $this->dataProcessors = $dataProcessors;
    }

}