<?php

namespace Kinintel\Services\Hook\Hook;

use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\ValueObjects\Hook\DatasourceHookUpdateMetaData;
use Kinintel\ValueObjects\Hook\Hook\PushFeedDatasourceHookConfig;
use Kinintel\ValueObjects\Hook\MetaData\SQLDatabaseDatasourceHookUpdateMetaData;

/**
 * Push feed datasource hook
 */
class PushFeedDatasourceHook implements DatasourceHook {

    public function __construct(private QueuedTaskService $queuedTaskService) {
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

        // Grab incoming feed parameter values
        $feedParameterValues = $hookConfig->getFeedParameterValues() ?? [];

        if ($updateData && $updateData[0] ?? null) {
            foreach ($feedParameterValues as $key => $value) {
                if (str_starts_with($value, "[[") && str_ends_with($value, "]]"))
                    $feedParameterValues[$key] = $updateData[0][trim($feedParameterValues[$key], " []")] ?? null;
            }
        }

        // Add auto increment value if specified
        if ($hookUpdateMetaData instanceof SQLDatabaseDatasourceHookUpdateMetaData && $hookConfig->getLastInsertIdParameterName()) {
            if (!$hookUpdateMetaData->getLastAutoIncrementId())
                return;
            $feedParameterValues[$hookConfig->getLastInsertIdParameterName()] = $hookUpdateMetaData->getLastAutoIncrementId();
        }


        $taskConfig = [
            "feedId" => $hookConfig->getFeedId(),
            "parameterValues" => $feedParameterValues,
            "statefulParameters" => $hookConfig->getStatefulParameterKeys(),
            "pushUrl" => $hookConfig->getPushUrl(),
            "headers" => $hookConfig->getOtherHeaders(),
            "method" => $hookConfig->getMethod()
        ];

        if ($hookConfig->getSignWithKeyPairId())
            $taskConfig["signingKeyPairId"] = $hookConfig->getSignWithKeyPairId();


        // Generate an incoming params hash
        $paramsHash = md5(join(":", array_values($hookConfig->getFeedParameterValues())));
        $taskTitle = "Push Feed -> " . $hookConfig->getPushUrl() . " (" . $paramsHash . ")";

        // Check existing tasks for queue - only queue if less than 2 items with proposed title.
        $existingItems = $this->queuedTaskService->listQueuedTasks("push-feed") ?? [];
        if (sizeof(ObjectArrayUtils::groupArrayOfObjectsByMember("description", $existingItems)[$taskTitle] ?? []) < 2) {
            $this->queuedTaskService->queueTask("push-feed", "pushfeed", $taskTitle, $taskConfig, null, 0);
        }

    }
}