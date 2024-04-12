<?php

namespace Kinintel\Services\DataProcessor;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;

abstract class BaseDataProcessor implements DataProcessor {


    /**
     * Save hook, called when an instance is saved.  Useful to modify state
     *
     * @param DataProcessorInstance $instance
     */
    public function onInstanceSave($instance) {
    }

    /**
     * Delete hook, called when an instance is deleted - useful to clean up database artifacts etc.
     *
     * @param DataProcessorInstance $instance
     */
    public function onInstanceDelete($instance) {
    }

    /**
     * Hook called when a related object is updated.  Useful if we need to modify state e.g. schema
     * when things change.
     *
     * @param DataProcessorInstance $instance
     * @param mixed $relatedObject
     */
    public function onRelatedObjectSave($instance, $relatedObject) {
    }

}