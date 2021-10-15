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
     * @var string
     * @required
     */
    private $cacheDatasourceParametersField = "parameters";

    /**
     * @var string
     * @required
     */
    private $cacheDatasourceCachedTimeField = "cached_time";

    /**
     * CachingDatasourceConfig constructor.
     *
     * @param string $sourceDatasourceKey
     * @param DatasourceInstance $sourceDatasource
     * @param string $cachingDatasourceKey
     * @param DatasourceInstance $cachingDatasource
     * @param int $cacheExpiryDays
     * @param int $cacheHours
     */
    public function __construct($sourceDatasourceKey = null, $sourceDatasource = null,
                                $cachingDatasourceKey = null, $cachingDatasource = null,
                                $cacheExpiryDays = null, $cacheHours = null) {
        $this->sourceDatasourceKey = $sourceDatasourceKey;
        $this->sourceDatasource = $sourceDatasource;
        $this->cacheDatasourceKey = $cachingDatasourceKey;
        $this->cacheDatasource = $cachingDatasource;
        $this->cacheExpiryDays = $cacheExpiryDays;
        $this->cacheExpiryHours = $cacheHours;
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


}