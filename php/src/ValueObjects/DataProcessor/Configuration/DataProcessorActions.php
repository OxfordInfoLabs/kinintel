<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration;

/**
 * Actions trait
 */
trait DataProcessorActions {

    /**
     * Get actions applicable to a given data processor - the processor instance key is passed in
     * so it can be used to construct data source keys as applicable.
     *
     * @param string $dataProcessorInstanceKey
     *
     * @return DataProcessorAction[]
     */
    public abstract function getProcessorActions($dataProcessorInstanceKey);

}