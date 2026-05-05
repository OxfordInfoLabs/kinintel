<?php


namespace Kinintel\Objects\Feed;

use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;

/**
 * @table ki_feed_webhook_instance
 */
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
     * @var string
     */
    protected $feedPath;

    /**
     * Webhook URL
     *
     * @var string
     */
    protected $url;

    /**
     * Webhook headers
     *
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    protected $headers;

    /**
     * JSON of config of webhook
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
     * @param string $feedPath
     * @param string $url
     * @param mixed $headers
     * @param mixed $config
     * @param mixed $lastState
     * @param mixed $lastStateConfig
     * @param ?int $id
     */
    public function __construct($feedPath = null, $url = null, $headers = null, $config = null, $lastState = null, $id = null) {
        $this->feedPath = $feedPath;
        $this->url = $url;
        $this->headers = $headers;
        $this->config = $config;
        $this->lastState = $lastState;
        $this->id = $id;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getFeedPath(): mixed {
        return $this->feedPath;
    }

    public function setFeedPath(string $feedPath): void {
        $this->feedPath = $feedPath;
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function setUrl(string $url): void {
        $this->url = $url;
    }

    public function getHeaders(): mixed {
        return $this->headers;
    }

    public function setHeaders(mixed $headers): void {
        $this->headers = $headers;
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