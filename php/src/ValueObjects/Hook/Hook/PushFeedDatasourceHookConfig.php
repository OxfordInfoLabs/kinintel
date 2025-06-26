<?php

namespace Kinintel\ValueObjects\Hook\Hook;

use Kinikit\Core\HTTP\Request\Request;

class PushFeedDatasourceHookConfig {

    /**
     *
     *
     * @param int $feedId
     * @param array $feedParameterValues
     * @param string|null $lastInsertIdParameterName
     * @param string $pushUrl
     * @param bool $signWithKeyPairId
     * @param array|null $otherHeaders
     * @param string|null $method
     */
    public function __construct(
        private int     $feedId,
        private ?array  $feedParameterValues = [],
        private ?string $lastInsertIdParameterName = null,
        private ?string $pushUrl = null,
        private bool $signWithKeyPairId = false,
        private array  $otherHeaders = [],
        private string $method = Request::METHOD_POST,) {
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
    public function getPushUrl(): ?string {
        return $this->pushUrl;
    }

    /**
     * @param string $pushUrl
     */
    public function setPushUrl(?string $pushUrl): void {
        $this->pushUrl = $pushUrl;
    }

    /**
     * @return string|null
     */
    public function getSignWithKeyPairId(): ?bool {
        return $this->signWithKeyPairId;
    }

    /**
     * @param bool $signWithKeyPairId
     */
    public function setSignWithKeyPairId(?bool $signWithKeyPairId): void {
        $this->signWithKeyPairId = $signWithKeyPairId;
    }

    /**
     * @return array|null
     */
    public function getOtherHeaders(): ?array {
        return $this->otherHeaders;
    }

    /**
     * @param array|null $otherHeaders
     */
    public function setOtherHeaders(?array $otherHeaders): void {
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