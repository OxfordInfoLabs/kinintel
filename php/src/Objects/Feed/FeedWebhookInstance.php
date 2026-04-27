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
     * The auto-generated id for this feed webhook instance
     *
     * @var integer
     */
    protected $id;

    /**
     * Associated feed id
     *
     * @var integer
     */
    protected $feedId;

    /**
     * JSON configuration data of the webhook instance
     *
     * @var mixed
     * @json
     */
    protected $config;

    /**
     * JSON configuration of the last state of the webhook instance
     *
     * @var mixed
     * @json
     */
    protected $lastState;


    /**
     * @param integer $feedId
     * @param mixed $config
     * @param mixed $lastState
     * @param int $accountId
     * @param string $projectKey
     */
    public function __construct($feedId = null, $config = null, $lastState = null, $accountId = null, $projectKey = null) {
        $this->feedId = $feedId;
        $this->config = $config;
        $this->lastState = $lastState;
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
    }

    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): void {
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

}