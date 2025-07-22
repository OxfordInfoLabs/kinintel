<?php

namespace Kinintel\Objects\Feed;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ki_push_feed
 */
class PushFeedSummary extends ActiveRecord {


    /**
     * @var array
     * @json
     */
    protected ?array $feedParameterValues = [];

    /**
     * @var array
     * @json
     */
    protected ?array $otherHeaders = [];

    /**
     * @var NotificationGroupSummary[]
     * @manyToMany
     * @linkTable ki_push_feed_notification_group
     */
    protected ?array $notificationGroups = [];


    public function __construct(protected ?string $description = "",
                                protected ?string $feedPath = "",
                                protected ?string $pushUrl = "",
                                protected ?string $feedSequenceParameterKey = "",
                                protected ?string $feedSequenceResultFieldName = "",
                                protected ?string $initialSequenceValue = "",
                                ?array            $feedParameterValues = [],
                                protected ?int    $signWithKeyPairId = null,
                                ?array            $otherHeaders = [],
                                protected ?string $method = Request::METHOD_POST,
                                protected ?string $triggerDatasourceKey = "",
                                array             $notificationGroups = [],
                                protected ?int    $id = null) {
        $this->feedParameterValues = $feedParameterValues ?? [];
        $this->otherHeaders = $otherHeaders ?? [];

        $this->setTriggerDatasourceKey($this->triggerDatasourceKey);
        $this->setDescription($this->description);

        $this->notificationGroups = $notificationGroups;
    }

    /**
     * @return array
     */
    public function getFeedParameterValues(): array {
        return $this->feedParameterValues;
    }

    /**
     * @param array $feedParameterValues
     */
    public function setFeedParameterValues(array $feedParameterValues): void {
        $this->feedParameterValues = $feedParameterValues;
    }

    /**
     * @return array
     */
    public function getOtherHeaders(): array {
        return $this->otherHeaders;
    }

    /**
     * @param array $otherHeaders
     */
    public function setOtherHeaders(array $otherHeaders): void {
        $this->otherHeaders = $otherHeaders;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getFeedPath(): string {
        return $this->feedPath;
    }

    /**
     * @param string $feedPath
     */
    public function setFeedPath(string $feedPath): void {
        $this->feedPath = $feedPath;
    }

    /**
     * @return string
     */
    public function getPushUrl(): string {
        return $this->pushUrl;
    }

    /**
     * @param string $pushUrl
     */
    public function setPushUrl(string $pushUrl): void {
        $this->pushUrl = $pushUrl;
    }

    /**
     * @return string
     */
    public function getFeedSequenceParameterKey(): string {
        return $this->feedSequenceParameterKey;
    }

    /**
     * @param string $feedSequenceParameterKey
     */
    public function setFeedSequenceParameterKey(string $feedSequenceParameterKey): void {
        $this->feedSequenceParameterKey = $feedSequenceParameterKey;
    }

    /**
     * @return string
     */
    public function getFeedSequenceResultFieldName(): string {
        return $this->feedSequenceResultFieldName;
    }

    /**
     * @param string $feedSequenceResultFieldName
     */
    public function setFeedSequenceResultFieldName(string $feedSequenceResultFieldName): void {
        $this->feedSequenceResultFieldName = $feedSequenceResultFieldName;
    }

    /**
     * @return string
     */
    public function getInitialSequenceValue(): string {
        return $this->initialSequenceValue;
    }

    /**
     * @param string $initialSequenceValue
     */
    public function setInitialSequenceValue(string $initialSequenceValue): void {
        $this->initialSequenceValue = $initialSequenceValue;
    }


    /**
     * @return int|null
     */
    public function getSignWithKeyPairId(): ?int {
        return $this->signWithKeyPairId;
    }

    /**
     * @param int|null $signWithKeyPairId
     */
    public function setSignWithKeyPairId(?int $signWithKeyPairId): void {
        $this->signWithKeyPairId = $signWithKeyPairId;
    }

    /**
     * @return string
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void {
        $this->method = $method;
    }

    /**
     * @return string|null
     */
    public function getTriggerDatasourceKey(): ?string {
        return $this->triggerDatasourceKey;
    }

    /**
     * @param string|null $triggerDatasourceKey
     */
    public function setTriggerDatasourceKey(?string $triggerDatasourceKey): void {
        $this->triggerDatasourceKey = $triggerDatasourceKey;
    }

    /**
     * @return array|null
     */
    public function getNotificationGroups(): ?array {
        return $this->notificationGroups;
    }

    /**
     * @param array|null $notificationGroups
     */
    public function setNotificationGroups(?array $notificationGroups): void {
        $this->notificationGroups = $notificationGroups;
    }


    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void {
        $this->id = $id;
    }


}