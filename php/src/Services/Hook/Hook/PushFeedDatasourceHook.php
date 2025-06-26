<?php

namespace Kinintel\Services\Hook\Hook;

use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\ValueObjects\Hook\DatasourceHookUpdateMetaData;
use Kinintel\ValueObjects\Hook\Hook\PushFeedDatasourceHookConfig;

/**
 * Push feed datasource hook
 */
class PushFeedDatasourceHook implements DatasourceHook {

    public function getConfigClass() {
        return PushFeedDatasourceHookConfig::class;
    }

    public function processHook($hookConfig, $updateMode, $updateData, DatasourceHookUpdateMetaData $hookUpdateMetaData = null) {
        // TODO: Implement processHook() method.
    }
}