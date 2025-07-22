<?php

namespace Kinintel\Objects\Feed;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Traits\Account\AccountProject;
use Kiniauth\ValueObjects\Util\LabelValue;
use Kinikit\Core\HTTP\Request\Request;
use Kinintel\Controllers\Admin\Datasource;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\Hook\DatasourceHook;

/**
 * @table ki_push_feed
 * @interceptor \Kinintel\Objects\Feed\PushFeedInterceptor
 * @generate
 */
class PushFeed extends PushFeedSummary {

    use AccountProject;

    /**
     * @var string
     */
    private ?string $lastPushedSequenceValue = null;

    /**
     * @var DatasourceHookInstance
     * @oneToOne
     * @childJoinColumns related_item_id
     */
    private ?DatasourceHookInstance $pushFeedHookInstance = null;


    /**
     * Construct a push feed with a summary and account and project
     *
     * @param PushFeedSummary|null $pushFeedSummary
     * @param string|null $projectKey
     * @param mixed $accountId
     */
    public function __construct(?PushFeedSummary $pushFeedSummary = null, ?string $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        parent::__construct($pushFeedSummary?->getDescription(),
            $pushFeedSummary?->getFeedPath(),
            $pushFeedSummary?->getPushUrl(),
            $pushFeedSummary?->getFeedSequenceParameterKey(),
            $pushFeedSummary?->getFeedSequenceResultFieldName(),
            $pushFeedSummary?->getInitialSequenceValue(),
            $pushFeedSummary?->getFeedParameterValues(),
            $pushFeedSummary?->getSignWithKeyPairId(),
            $pushFeedSummary?->getOtherHeaders(),
            $pushFeedSummary?->getMethod(),
            $pushFeedSummary?->getTriggerDatasourceKey(),
            $pushFeedSummary?->getFailedPushNotificationTitle(),
            $pushFeedSummary?->getFailedPushNotificationDescription(),
            $pushFeedSummary?->getNotificationGroups(),
            $pushFeedSummary?->getId());

        $this->setAccountId($accountId);
        $this->setProjectKey($projectKey);

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
     * @return DatasourceHookInstance
     */
    public function getPushFeedHookInstance(): ?DatasourceHookInstance {
        if (!$this->pushFeedHookInstance)
            $this->pushFeedHookInstance = new DatasourceHookInstance(
                $this->getDescription() . "-> Hook",
                $this->getTriggerDatasourceKey(),
                "pushfeed",
                hookConfig: ["id" => $this->getId()],
                accountId: $this->getAccountId(),
                projectKey: $this->getProjectKey());
        return $this->pushFeedHookInstance;
    }

    /**
     * @param DatasourceHookInstance $pushFeedHookInstance
     */
    public function setPushFeedHookInstance(?DatasourceHookInstance $pushFeedHookInstance): void {
        $this->pushFeedHookInstance = $pushFeedHookInstance;
    }

    public function setDescription(?string $description): void {
        parent::setDescription($description);
        $this->getPushFeedHookInstance()->setTitle($this->getDescription() . "-> Hook");
    }


    public function setTriggerDatasourceKey(?string $triggerDatasourceKey): void {
        parent::setTriggerDatasourceKey($triggerDatasourceKey);
        $this->getPushFeedHookInstance()->setDatasourceInstanceKey($triggerDatasourceKey);
    }

    public function setAccountId($accountId) {
        $this->accountId = $accountId;
        $this->getPushFeedHookInstance()->setAccountId($accountId);
    }

    public function setProjectKey($projectKey) {
        $this->projectKey = $projectKey;
        $this->getPushFeedHookInstance()->setProjectKey($projectKey);
    }


    /**
     * Generate a summary from a push feed.
     *
     * @return PushFeedSummary
     */
    public function generateSummary() {
        return new PushFeedSummary($this->getDescription(),
            $this->getFeedPath(), $this->getPushUrl(), $this->getFeedSequenceParameterKey(), $this->getFeedSequenceResultFieldName(),
            $this->getInitialSequenceValue(),
            $this->getFeedParameterValues(), $this->getSignWithKeyPairId(), $this->getOtherHeaders(),
            $this->getMethod(), $this->getTriggerDatasourceKey(),
            $this->getFailedPushNotificationTitle(),
            $this->getFailedPushNotificationDescription(),
            $this->getNotificationGroups(),
            $this->getId());
    }

}