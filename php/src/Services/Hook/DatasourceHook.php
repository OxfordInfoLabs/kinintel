<?php

namespace Kinintel\Services\Hook;

use Kinintel\ValueObjects\Hook\DatasourceHookConfig;
use Kinintel\ValueObjects\Hook\DatasourceHookUpdateMetaData;


/**
 * @implementation pushfeed \Kinintel\Services\Hook\Hook\PushFeedDatasourceHook
 */
interface DatasourceHook {


    /**
     * @return string
     */
    public function getConfigClass();

    /**
     * @param DatasourceHookConfig $hookConfig
     * @param string $updateMode
     * @param mixed $updateData
     * @param ?DatasourceHookUpdateMetaData|null $hookUpdateMetaData
     *
     * @return mixed
     */
    public function processHook($hookConfig, $updateMode, $updateData, ?DatasourceHookUpdateMetaData $hookUpdateMetaData = null);

}