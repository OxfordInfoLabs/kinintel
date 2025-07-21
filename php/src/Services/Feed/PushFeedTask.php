<?php

namespace Kinintel\Services\Feed;

use Kiniauth\Services\Workflow\Task\Task;

class PushFeedTask implements Task {


    /**
     * @param FeedService $feedService
     */
    public function __construct(private FeedService $feedService) {
    }


    /**
     * Execute push feed via service
     *
     * @param $configuration
     * @return void
     */
    public function run($configuration) {
        if ($configuration["pushFeedId"] ?? null) {
            $this->feedService->executePushFeed($configuration["pushFeedId"]);
        }
    }
}