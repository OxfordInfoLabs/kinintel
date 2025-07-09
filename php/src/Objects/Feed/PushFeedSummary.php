<?php

namespace Kinintel\Objects\Feed;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ki_push_feed_state
 */
class PushFeedSummary extends ActiveRecord {


    /**
     * @var array
     * @json
     */
    private array $feedParameterValues = [];

    /**
     * @var array
     * @json
     */
    private array $statefulParameterKeys = [];

    /**
     * @var array
     * @json
     */
    private array $otherHeaders = [];

    public function __construct(protected string  $description,
                                protected string  $feedPath,
                                protected string  $pushUrl,
                                array             $feedParameterValues = [],
                                array             $statefulParameterKeys = [],
                                protected ?string $lastInsertIdParameterName = null,
                                protected ?int    $signWithKeyPairId = null,
                                array             $otherHeaders = [],
                                protected string  $method = Request::METHOD_POST,
                                protected ?int    $id = null) {

    }

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void {
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
    public function getStatefulParameterKeys(): array {
        return $this->statefulParameterKeys;
    }

    /**
     * @param array $statefulParameterKeys
     */
    public function setStatefulParameterKeys(array $statefulParameterKeys): void {
        $this->statefulParameterKeys = $statefulParameterKeys;
    }

    /**
     * @return string|null
     */
    public function getLastInsertIdParameterName(): ?string {
        return $this->lastInsertIdParameterName;
    }

    /**
     * @param string|null $lastInsertIdParameterName
     */
    public function setLastInsertIdParameterName(?string $lastInsertIdParameterName): void {
        $this->lastInsertIdParameterName = $lastInsertIdParameterName;
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
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void {
        $this->method = $method;
    }


}