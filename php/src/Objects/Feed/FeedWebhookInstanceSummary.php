<?php


namespace Kinintel\Objects\Feed;

use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;

class FeedWebhookInstanceSummary extends ActiveRecord {

    /**
     * Unique primary key
     *
     * @autoIncrement
     */
    protected $id;

    /**
     * Feed ID
     *
     * @var integer
     */
    protected $feedId;

    /**
     * config of webhook
     *
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    protected $config;

    /**
     * JSON of last state
     *
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    protected $lastState;

    /**
     * @param int $feedId
     * @param mixed $config
     * @param mixed $lastState
     */
    public function __construct($feedId = null, $config = null, $lastState = null) {
        $this->feedId = $feedId;
        $this->config = $config;
        $this->lastState = $lastState;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getFeedId(): mixed {
        return $this->feedId;
    }

    public function setFeedId(mixed $feedId): void {
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