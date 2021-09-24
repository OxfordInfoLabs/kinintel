<?php


namespace Kinintel\Objects\DataProcessor;


use Kiniauth\Traits\Account\AccountProject;

/**
 * Data processor instance
 *
 * @table ki_dataprocessor_instance
 * @generate
 */
class DataProcessorInstance extends DataProcessorInstanceSummary {

    // use account project trait
    use AccountProject;


    /**
     * Type for this data source - can either be a mapping implementation key
     * or a fully qualified class path
     *
     * @var string
     */
    private $type;

    /**
     * @var mixed
     * @json
     */
    private $config;

    /**
     * DataProcessorInstance constructor.
     * @param string $type
     * @param mixed $config
     */
    public function __construct($key, $title, $type, $config = [], $projectKey = null, $accountId = null) {
        parent::__construct($key, $title);

        $this->type = $type;
        $this->config = $config;
        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
    }


    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config) {
        $this->config = $config;
    }


    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }


}