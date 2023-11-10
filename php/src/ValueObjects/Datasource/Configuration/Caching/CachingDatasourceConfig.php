<?php


namespace Kinintel\ValueObjects\Datasource\Configuration\Caching;

use Kinintel\Objects\Datasource\DatasourceInstance;

/**
 * Configuration for caching datasource
 *
 * Class CachingDatasourceConfig
 * @package Kinintel\ValueObjects\Datasource\Configuration\Caching
 */
class CachingDatasourceConfig {

    /**
     * @var string
     * @requiredEither sourceDatasource
     */
    private $sourceDatasourceKey;

    /**
     * @var DatasourceInstance
     */
    private $sourceDatasource;


    /**
     * @var string
     * @requiredEither cacheDatasource
     */
    private $cacheDatasourceKey;


    /**
     * @var DatasourceInstance
     */
    private $cacheDatasource;


    /**
     * One of the cache mode constants.  This controls
     * whether newly accessed records represent
     * 1) a complete data set (the default)
     * 2) an incremental addition to the whole data set.
     * 3) an update on the whole data set.
     *
     * @var string
     */
    private $cacheMode = self::CACHE_MODE_COMPLETE;


    /**
     * Number of days before cache expiry of entries.
     * Additive to hours entry
     *
     * @var int
     * @requiredEither cacheExpiryHours
     */
    private $cacheExpiryDays;

    /**
     * Number of hours before cache expiry of entries.
     * Additive to days entry
     *
     * @var int
     */
    private $cacheExpiryHours;


    /**
     * Flag indicating whether older results should be returned if available
     * in the case that no results are returned from the source datasource.
     *
     * @var bool
     */
    private $fallbackToOlder = false;


    /**
     * @var string
     * @required
     */
    private $cacheDatasourceParametersField = "parameters";

    /**
     * @var string
     * @required
     */
    private $cacheDatasourceCachedTimeField = "cached_time";

    private bool $hashing = false;


    // Cache mode constants
    const CACHE_MODE_COMPLETE = "complete";
    const CACHE_MODE_INCREMENTAl = "incremental";
    const CACHE_MODE_UPDATE = "update";

    /**
     * CachingDatasourceConfig constructor.
     *
     * @param string $sourceDatasourceKey
     * @param DatasourceInstance $sourceDatasource
     * @param string $cachingDatasourceKey
     * @param DatasourceInstance $cachingDatasource
     * @param int $cacheExpiryDays
     * @param int $cacheHours
     * @poram bool $fallbackToOlder
     * @param string $cacheMode
     * @param bool $hashing
     */
    public function __construct($sourceDatasourceKey = null, $sourceDatasource = null,
                                $cachingDatasourceKey = null, $cachingDatasource = null,
                                $cacheExpiryDays = null, $cacheHours = null, $fallbackToOlder = false,
                                $cacheMode = self::CACHE_MODE_COMPLETE, $hashing = false) {
        $this->sourceDatasourceKey = $sourceDatasourceKey;
        $this->sourceDatasource = $sourceDatasource;
        $this->cacheDatasourceKey = $cachingDatasourceKey;
        $this->cacheDatasource = $cachingDatasource;
        $this->cacheExpiryDays = $cacheExpiryDays;
        $this->cacheExpiryHours = $cacheHours;
        $this->fallbackToOlder = $fallbackToOlder;
        $this->cacheMode = $cacheMode;
        $this->hashing = $hashing;
    }


    /**
     * @return string
     */
    public function getSourceDatasourceKey() {
        return $this->sourceDatasourceKey;
    }

    /**
     * @param string $sourceDatasourceKey
     */
    public function setSourceDatasourceKey($sourceDatasourceKey) {
        $this->sourceDatasourceKey = $sourceDatasourceKey;
    }

    /**
     * @return DatasourceInstance
     */
    public function getSourceDatasource() {
        return $this->sourceDatasource;
    }

    /**
     * @param DatasourceInstance $sourceDatasource
     */
    public function setSourceDatasource($sourceDatasource) {
        $this->sourceDatasource = $sourceDatasource;
    }

    /**
     * @return string
     */
    public function getCacheDatasourceKey() {
        return $this->cacheDatasourceKey;
    }

    /**
     * @param string $cacheDatasourceKey
     */
    public function setCacheDatasourceKey($cacheDatasourceKey) {
        $this->cacheDatasourceKey = $cacheDatasourceKey;
    }

    /**
     * @return DatasourceInstance
     */
    public function getCacheDatasource() {
        return $this->cacheDatasource;
    }

    /**
     * @param DatasourceInstance $cacheDatasource
     */
    public function setCacheDatasource($cacheDatasource) {
        $this->cacheDatasource = $cacheDatasource;
    }

    /**
     * @return string
     */
    public function getCacheMode() {
        return $this->cacheMode;
    }

    /**
     * @param string $cacheMode
     */
    public function setCacheMode($cacheMode) {
        $this->cacheMode = $cacheMode;
    }

    /**
     * @return int
     */
    public function getCacheExpiryDays() {
        return $this->cacheExpiryDays;
    }

    /**
     * @param int $cacheExpiryDays
     */
    public function setCacheExpiryDays($cacheExpiryDays) {
        $this->cacheExpiryDays = $cacheExpiryDays;
    }

    /**
     * @return int
     */
    public function getCacheExpiryHours() {
        return $this->cacheExpiryHours;
    }

    /**
     * @param int $cacheExpiryHours
     */
    public function setCacheExpiryHours($cacheExpiryHours) {
        $this->cacheExpiryHours = $cacheExpiryHours;
    }

    /**
     * @return string
     */
    public function getCacheDatasourceParametersField() {
        return $this->cacheDatasourceParametersField;
    }

    /**
     * @param string $cacheDatasourceParametersField
     */
    public function setCacheDatasourceParametersField($cacheDatasourceParametersField) {
        $this->cacheDatasourceParametersField = $cacheDatasourceParametersField;
    }

    /**
     * @return string
     */
    public function getCacheDatasourceCachedTimeField() {
        return $this->cacheDatasourceCachedTimeField;
    }

    /**
     * @param string $cacheDatasourceCachedTimeField
     */
    public function setCacheDatasourceCachedTimeField($cacheDatasourceCachedTimeField) {
        $this->cacheDatasourceCachedTimeField = $cacheDatasourceCachedTimeField;
    }

    /**
     * @return bool
     */
    public function isFallbackToOlder() {
        return $this->fallbackToOlder;
    }

    /**
     * @param bool $fallbackToOlder
     */
    public function setFallbackToOlder($fallbackToOlder) {
        $this->fallbackToOlder = $fallbackToOlder;
    }

    public function isHashing(): ?bool {
        return $this->hashing;
    }

    public function setHashing(?bool $hashing): void {
        $this->hashing = $hashing;
    }


}