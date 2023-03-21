<?php


namespace Kinintel\ValueObjects\Dashboard;


class DashboardExternalSettings {

    /**
     * @var boolean
     */
    private $cacheEnabled;

    /**
     * @var integer
     */
    private $cacheTimeSeconds;

    /**
     * DashboardExternalSettings constructor.
     *
     * @param bool $cacheEnabled
     * @param int $cacheTimeSeconds
     */
    public function __construct($cacheEnabled = false, $cacheTimeSeconds = 0) {
        $this->cacheEnabled = $cacheEnabled;
        $this->cacheTimeSeconds = $cacheTimeSeconds;
    }


    /**
     * @return bool
     */
    public function isCacheEnabled() {
        return $this->cacheEnabled;
    }

    /**
     * @param bool $cacheEnabled
     */
    public function setCacheEnabled($cacheEnabled) {
        $this->cacheEnabled = $cacheEnabled;
    }

    /**
     * @return int
     */
    public function getCacheTimeSeconds() {
        return $this->cacheTimeSeconds;
    }

    /**
     * @param int $cacheTimeSeconds
     */
    public function setCacheTimeSeconds($cacheTimeSeconds) {
        $this->cacheTimeSeconds = $cacheTimeSeconds;
    }


}