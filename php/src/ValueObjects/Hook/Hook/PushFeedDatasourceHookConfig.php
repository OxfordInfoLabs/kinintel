<?php

namespace Kinintel\ValueObjects\Hook\Hook;

class PushFeedDatasourceHookConfig {

    /**
     * Construct with push feed id.
     *
     * @param int|null $pushFeedId
     */
    public function __construct(private ?int $pushFeedId) {
    }

    /**
     * @return int|null
     */
    public function getPushFeedId(): ?int {
        return $this->pushFeedId;
    }

    /**
     * @param int|null $pushFeedId
     */
    public function setPushFeedId(?int $pushFeedId): void {
        $this->pushFeedId = $pushFeedId;
    }

}