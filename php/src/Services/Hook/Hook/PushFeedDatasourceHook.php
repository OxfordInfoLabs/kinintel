<?php

namespace Kinintel\Services\Hook\Hook;

use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Services\Feed\FeedService;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\ValueObjects\Hook\DatasourceHookUpdateMetaData;
use Kinintel\ValueObjects\Hook\Hook\PushFeedDatasourceHookConfig;
use Kinintel\ValueObjects\Hook\MetaData\SQLDatabaseDatasourceHookUpdateMetaData;

/**
 * Push feed datasource hook
 */
class PushFeedDatasourceHook implements DatasourceHook {

    public function __construct(private FeedService $feedService) {
    }


    public function getConfigClass() {
        return PushFeedDatasourceHookConfig::class;
    }

    /**
     * Evaluate incoming data and create a push feed queued task to
     *
     * @param PushFeedDatasourceHookConfig $hookConfig
     * @param $updateMode
     * @param $updateData
     * @param DatasourceHookUpdateMetaData|null $hookUpdateMetaData
     * @return void
     */
    public function processHook($hookConfig, $updateMode, $updateData, DatasourceHookUpdateMetaData $hookUpdateMetaData = null) {

        if (sizeof($updateData) == 0) {
            return;
        }

        // Queue the push feed
        $this->feedService->queuePushFeed($hookConfig->getPushFeedId());

    }
}