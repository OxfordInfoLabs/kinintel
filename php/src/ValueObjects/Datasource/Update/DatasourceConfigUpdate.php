<?php

namespace Kinintel\ValueObjects\Datasource\Update;

class DatasourceConfigUpdate {

    /**
     * @var string
     */
    private $title;

    /**
     * @var mixed
     */
    private $config;

    /**
     * @param string $title
     * @param mixed $config
     */
    public function __construct($title, $config)
    {
        $this->title = $title;
        $this->config = $config;
    }


    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void {
        $this->title = $title;
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
    public function setConfig($config): void {
        $this->config = $config;
    }



}
