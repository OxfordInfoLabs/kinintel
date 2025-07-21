<?php

namespace Kinintel\Objects\Feed;

use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

class PushFeedInterceptor extends DefaultORMInterceptor {

    // Post save logic
    public function postSave($object) {
        $hook = $object->getPushFeedHookInstance();
        if ($hook) {
            $hook->setHookConfig(["pushFeedId" => $object->getId()]);
            $hook->save();
        }
    }


}