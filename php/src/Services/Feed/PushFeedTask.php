<?php

namespace Kinintel\Services\Feed;

use Kiniauth\Services\Workflow\Task\Task;

class PushFeedTask implements Task {


    /**
     * @param PushFeedService $pushFeedService
     */
    public function __construct(private PushFeedService $pushFeedService) {
    }


    /**
     * Execute push feed via service
     *
     * @param $configuration
     * @return void
     */
    public function run($configuration) {
        if ($configuration["pushFeedId"] ?? null) {
            $this->pushFeedService->processPushFeed($configuration["pushFeedId"]);
        }
    }
}