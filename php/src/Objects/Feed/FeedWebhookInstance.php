<?php

namespace Kinintel\Objects\Feed;

use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ki_feed_webhook_instance
 * @generate
 */
class FeedWebhookInstance extends ActiveRecord {

    use AccountProject;


    /**
     * @var ?int
     */
    protected ?int $id = null;

    /**
     * @var ?int
     */
    protected ?int $feedId = null;

    /**
     * @json
     */
    protected mixed $config;

    /**
     * @json
     */
    protected mixed $lastState;

    protected $accountId;

    protected $projectKey;


    /**
     * @param int|null $feedId
     * @param mixed|null $config
     * @param mixed|null $lastState
     * @param int|null $accountId
     * @param string|null $projectKey
     */
    public function __construct(?int $feedId = null, mixed $config = null, mixed $lastState = null, ?int $accountId = null, ?string $projectKey = null) {
        $this->feedId = $feedId;
        $this->config = $config;
        $this->lastState = $lastState;
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function getFeedId(): ?int {
        return $this->feedId;
    }

    public function setFeedId(?int $feedId): void {
        $this->feedId = $feedId;
    }

    public function getConfig(): mixed {
        return $this->config;
    }

    public function setConfig(mixed $config): void {
        $this->config = $config;
    }

    public function getLastState(): mixed {
        return $this->lastState;
    }

    public function setLastState(mixed $lastState): void {
        $this->lastState = $lastState;
    }

    public function getAccountId(): ?string {
        return $this->accountId;
    }

    public function setAccountId(?string $accountId): void {
        $this->accountId = $accountId;
    }

    public function getProjectKey(): ?string {
        return $this->projectKey;
    }

    public function setProjectKey(?string $projectKey): void {
        $this->projectKey = $projectKey;
    }




}