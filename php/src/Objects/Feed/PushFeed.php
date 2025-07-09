<?php

namespace Kinintel\Objects\Feed;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\HTTP\Request\Request;

/**
 * @table ki_push_feed_state
 * @generate
 */
class PushFeed extends PushFeedSummary {

    use AccountProject;

    /**
     * @json
     * @var array
     */
    private array $lastPushStatefulParameterValues = [];

    /**
     * Construct a push feed with a summary and account and project
     *
     * @param PushFeedSummary|null $pushFeedSummary
     * @param string|null $projectKey
     * @param mixed $accountId
     */
    public function __construct(?PushFeedSummary $pushFeedSummary = null, ?string $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        if ($pushFeedSummary)
            parent::__construct($pushFeedSummary->getDescription(),
                $pushFeedSummary->getFeedPath(),
                $pushFeedSummary->getPushUrl(),
                $pushFeedSummary->getFeedParameterValues(),
                $pushFeedSummary->getStatefulParameterKeys(),
                $pushFeedSummary->getLastInsertIdParameterName(),
                $pushFeedSummary->getSignWithKeyPairId(),
                $pushFeedSummary->getOtherHeaders(),
                $pushFeedSummary->getMethod(),
                $pushFeedSummary->getId());

        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
    }

    /**
     * @return array
     */
    public function getLastPushStatefulParameterValues(): array {
        return $this->lastPushStatefulParameterValues;
    }

    /**
     * @param array $lastPushStatefulParameterValues
     */
    public function setLastPushStatefulParameterValues(array $lastPushStatefulParameterValues): void {
        $this->lastPushStatefulParameterValues = $lastPushStatefulParameterValues;
    }

    /**
     * Generate a summary from a push feed.
     *
     * @return PushFeedSummary
     */
    public function generateSummary() {
        return new PushFeedSummary($this->getDescription(),
            $this->getFeedPath(), $this->getPushUrl(), $this->getFeedParameterValues(), $this->getStatefulParameterKeys(),
            $this->getLastInsertIdParameterName(), $this->getSignWithKeyPairId(), $this->getOtherHeaders(),
            $this->getMethod(), $this->getId());
    }

}