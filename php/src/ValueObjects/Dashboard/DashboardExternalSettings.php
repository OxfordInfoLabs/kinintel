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
     * @var boolean
     */
    private $darkMode;

    /**
     * @var boolean
     */
    private $showParameters;

    /**
     * @var integer
     */
    private $refreshInterval;

    /**
     * DashboardExternalSettings constructor.
     *
     * @param bool $cacheEnabled
     * @param int $cacheTimeSeconds
     * @param bool $darkMode
     * @param bool $showParameters
     * @param int $refreshInterval
     */
    public function __construct($cacheEnabled = false, $cacheTimeSeconds = 0, $darkMode = false, $showParameters = false, $refreshInterval = 0) {
        $this->cacheEnabled = $cacheEnabled;
        $this->cacheTimeSeconds = $cacheTimeSeconds;
        $this->darkMode = $darkMode;
        $this->showParameters = $showParameters;
        $this->refreshInterval = $refreshInterval;
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

    /**
     * @return bool
     */
    public function getDarkMode() {
        return $this->darkMode;
    }

    /**
     * @param bool $darkMode
     */
    public function setDarkMode($darkMode) {
        $this->darkMode = $darkMode;
    }

    /**
     * @return bool
     */
    public function getShowParameters() {
        return $this->showParameters;
    }

    /**
     * @param bool $showParameters
     */
    public function setShowParameters($showParameters) {
        $this->showParameters = $showParameters;
    }

    /**
     * @return int
     */
    public function getRefreshInterval() {
        return $this->refreshInterval;
    }

    /**
     * @param int $refreshInterval
     */
    public function setRefreshInterval($refreshInterval) {
        $this->refreshInterval = $refreshInterval;
    }


}
