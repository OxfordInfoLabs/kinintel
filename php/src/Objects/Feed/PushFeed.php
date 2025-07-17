<?php

namespace Kinintel\Objects\Feed;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\HTTP\Request\Request;

/**
 * @table ki_push_feed
 * @generate
 */
class PushFeed extends PushFeedSummary {

    use AccountProject;

    /**
     * @var string
     */
    private ?string $lastPushedSequenceValue = null;

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
                $pushFeedSummary->getFeedSequenceParameterKey(),
                $pushFeedSummary->getFeedSequenceResultFieldName(),
                $pushFeedSummary->getFeedParameterValues(),
                $pushFeedSummary->getSignWithKeyPairId(),
                $pushFeedSummary->getOtherHeaders(),
                $pushFeedSummary->getMethod(),
                $pushFeedSummary->getId());

        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
    }

    /**
     * @return string
     */
    public function getLastPushedSequenceValue(): ?string {
        return $this->lastPushedSequenceValue;
    }

    /**
     * @param string $lastPushedSequenceValue
     */
    public function setLastPushedSequenceValue(?string $lastPushedSequenceValue): void {
        $this->lastPushedSequenceValue = $lastPushedSequenceValue;
    }


    /**
     * Generate a summary from a push feed.
     *
     * @return PushFeedSummary
     */
    public function generateSummary() {
        return new PushFeedSummary($this->getDescription(),
            $this->getFeedPath(), $this->getPushUrl(), $this->getFeedSequenceParameterKey(), $this->getFeedSequenceResultFieldName(),
            $this->getFeedParameterValues(), $this->getSignWithKeyPairId(), $this->getOtherHeaders(),
            $this->getMethod(), $this->getId());
    }

}