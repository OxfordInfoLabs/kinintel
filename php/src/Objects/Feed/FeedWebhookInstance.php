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
                $feedWebhookSummary->getFeedPath(),
                $feedWebhookSummary->getUrl(),
                $feedWebhookSummary->getHeaders(),
                $feedWebhookSummary->getConfig(),
                $feedWebhookSummary->getLastState(),
                $feedWebhookSummary->getId()
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
            $this->getFeedPath(),
            $this->getUrl(),
            $this->getHeaders(),
            $this->getConfig(),
            $this->getLastState(),
            $this->getId()
        );
    }

}