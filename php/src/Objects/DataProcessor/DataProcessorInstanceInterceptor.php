<?php

namespace Kinintel\Objects\DataProcessor;

use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

class DataProcessorInstanceInterceptor extends DefaultORMInterceptor {


    /**
     * Implement post save to call instance save method on the processor.
     *
     * @param DataProcessorInstance $object
     * @return void
     */
    public function preSave($object) {
        $object->returnProcessor()->onInstanceSave($object);
    }

    /**
     * Implement pre delete to all instance delete method on the processor.
     *
     * @param DataProcessorInstance $object
     * @return void
     */
    public function preDelete($object) {
        $object->returnProcessor()->onInstanceDelete($object);
    }


}