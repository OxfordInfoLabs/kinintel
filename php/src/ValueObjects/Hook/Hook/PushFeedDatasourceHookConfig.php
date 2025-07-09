<?php

namespace Kinintel\ValueObjects\Hook\Hook;

use Kinikit\Core\HTTP\Request\Request;

class PushFeedDatasourceHookConfig {

    /**
     *
     *
     * @param int $feedId
     * @param string $pushUrl
     * @param array $feedParameterValues
     * @param string[] $statefulParameterKeys
     * @param string|null $lastInsertIdParameterName
     * @param bool $signWithKeyPairId
     * @param string[] $otherHeaders
     * @param string|null $method
     */
    public function __construct(
        private int     $feedId,
        private string  $pushUrl,
        private array  $feedParameterValues = [],
        private array  $statefulParameterKeys = [],
        private ?string $lastInsertIdParameterName = null,
        private ?int    $signWithKeyPairId = null,
        private array   $otherHeaders = [],
        private string  $method = Request::METHOD_POST) {
    }


    /**
     * @return int
     */
    public function getFeedId(): int {
        return $this->feedId;
    }

    /**
     * @param int $feedId
     */
    public function setFeedId(int $feedId): void {
        $this->feedId = $feedId;
    }

    /**
     * @return array
     */
    public function getFeedParameterValues(): ?array {
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
     * @return string|null
     */
    public function getSignWithKeyPairId(): ?int {
        return $this->signWithKeyPairId;
    }

    /**
     * @param bool $signWithKeyPairId
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
     * @return string|null
     */
    public function getMethod(): ?string {
        return $this->method;
    }

    /**
     * @param string|null $method
     */
    public function setMethod(?string $method): void {
        $this->method = $method;
    }


}