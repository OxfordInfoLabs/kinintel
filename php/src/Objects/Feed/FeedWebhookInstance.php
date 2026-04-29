<?php

namespace Kinintel\Objects\Feed;

use Kiniauth\Traits\Account\AccountProject;
use Kinintel\Objects\Feed\FeedWebhookInstanceSummary;

/**
 * @table ki_feed_webhook_instance
 * @generate
 */
class FeedWebhookInstance extends FeedWebhookInstanceSummary {
    use AccountProject;

    /**
     * @param FeedWebhookInstanceSummary $feedWebhookSummary
     * @param string $projectKey
     * @param integer $accountId
     */
    public function __construct( $feedWebhookSummary, $projectKey = null, $accountId = null) {
        if ($feedWebhookSummary) {
            parent::__construct(
                $feedWebhookSummary->getFeedId(),
                $feedWebhookSummary->getConfig(),
                $feedWebhookSummary->getLastState()
            );
        }
        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
    }

    /**
     * Return a summary object
     *
     * @returns FeedWebhookInstanceSummary
     */
    public function returnSummary() {
        return new FeedWebhookInstanceSummary(
            $this->getFeedId(),
            $this->getConfig(),
            $this->getLastState()
        );
    }

}