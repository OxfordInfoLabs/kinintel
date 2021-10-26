<?php


namespace Kinintel\Objects\Datasource\Caching;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Caching\CachingDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;

class CachingDatasource extends BaseDatasource {

    /**
     * @var DatasourceService
     */
    private $datasourceService;


    public function __construct($config = null, $authenticationCredentials = null, $validator = null) {
        parent::__construct($config, $authenticationCredentials, $validator);
        $this->datasourceService = Container::instance()->get(DatasourceService::class);
    }


    /**
     * Get caching data source config classr
     *
     * @return string
     */
    public function getConfigClass() {
        return CachingDatasourceConfig::class;
    }


    /**
     * No directly supported transformations here
     *
     * @return array
     */
    public function getSupportedTransformationClasses() {
        return [];
    }

    /**
     * Disable authentication
     *
     * @return false
     */
    public function isAuthenticationRequired() {
        return false;
    }


    /**
     * No transformations to apply here - we assume conversion to default dataset
     * in each instance
     *
     * @param \Kinintel\ValueObjects\Transformation\Transformation $transformation
     * @param array $parameterValues
     * @return BaseDatasource|void
     */
    public function applyTransformation($transformation, $parameterValues = []) {
    }

    /**
     * @param DatasourceService $datasourceService
     */
    public function setDatasourceService($datasourceService) {
        $this->datasourceService = $datasourceService;
    }


    /**
     * Materialise dataset
     *
     * @param array $parameterValues
     * @return Dataset|void
     */
    public function materialiseDataset($parameterValues = []) {

        /**
         * @var CachingDatasourceConfig $config
         */
        $config = $this->getConfig();

        /**
         * @var DatasourceInstance $sourceDatasourceInstance
         */
        $sourceDatasourceInstance = $config->getSourceDatasource() ?? $this->datasourceService->getDataSourceInstanceByKey($config->getSourceDatasourceKey());

        /**
         * @var DatasourceInstance $cacheDatasourceInstance
         */
        $cacheDatasourceInstance = $config->getCacheDatasource() ?? $this->datasourceService->getDataSourceInstanceByKey($config->getCacheDatasourceKey());

        // Check the cache by doing a very limited query based upon
        // cache limits
        $now = (new \DateTime())->format("Y-m-d H:i:s");
        $cacheThresholdObj = new \DateTime();
        if ($config->getCacheExpiryDays()) $cacheThresholdObj->sub(new \DateInterval("P" . $config->getCacheExpiryDays() . "D"));
        if ($config->getCacheExpiryHours()) $cacheThresholdObj->sub(new \DateInterval("PT" . $config->getCacheExpiryHours() . "H"));
        $cacheThreshold = $cacheThresholdObj->format("Y-m-d H:i:s");

        // Encode parameters
        $encodedParameters = json_encode($parameterValues);

        // Do a limited check on cache data source
        $cacheCheckDatasource = $cacheDatasourceInstance->returnDataSource();
        $cacheCheckDatasource = $cacheCheckDatasource->applyTransformation(new FilterTransformation([
            new Filter("[[" . $config->getCacheDatasourceParametersField() . "]]", $encodedParameters),
            new Filter("[[" . $config->getCacheDatasourceCachedTimeField() . "]]", $cacheThreshold, Filter::FILTER_TYPE_GREATER_THAN)
        ]));
        $cacheCheckDatasource = $cacheCheckDatasource->applyTransformation(new PagingTransformation(1, 0));

        // Materialise the cache data source
        $cacheCheckDataset = $cacheCheckDatasource->materialise();

        // Grab a new data source for return results
        $cacheDatasource = $cacheDatasourceInstance->returnDataSource();

        // If no rows returned from cache, materialise the source dataset
        // And store in cache
        $noSourceResults = false;
        if (!$cacheCheckDataset->nextDataItem()) {

            $noSourceResults = true;

            // Materialise the source data set using parameters
            $sourceDatasource = $sourceDatasourceInstance->returnDataSource();
            $sourceDataset = $sourceDatasource->materialise($parameterValues);

            // Create merged fields ready for insert
            $targetFields = [
                new Field($config->getCacheDatasourceParametersField()),
                new Field($config->getCacheDatasourceCachedTimeField())
            ];
            // Recreate target fields to ensure we remove any mapping rules to avoid strangeness.
            foreach ($sourceDataset->getColumns() as $column) {
                $targetFields[] = new Field($column->getName(), $column->getTitle());
            }

            // Insert in batches of 50
            $batch = [];
            while ($sourceItem = $sourceDataset->nextDataItem()) {
                $noSourceResults = false;
                $batch[] = array_merge([$config->getCacheDatasourceParametersField() => $encodedParameters,
                    $config->getCacheDatasourceCachedTimeField() => $now], $sourceItem);
                if (sizeof($batch) == 50) {
                    $cacheDatasource->update(new ArrayTabularDataset($targetFields, $batch));
                    $batch = [];
                }
            }
            if (sizeof($batch)) {
                $cacheDatasource->update(new ArrayTabularDataset($targetFields, $batch));
            }

        }


        // if no source results and we have a fallback to older rule in place, move the cache threshold back to the
        // previous timeframe to allow previous results to flow through if they exist
        if ($noSourceResults && $config->isFallbackToOlder()) {
            if ($config->getCacheExpiryDays()) $cacheThresholdObj->sub(new \DateInterval("P" . $config->getCacheExpiryDays() . "D"));
            if ($config->getCacheExpiryHours()) $cacheThresholdObj->sub(new \DateInterval("PT" . $config->getCacheExpiryHours() . "H"));
            $cacheThreshold = $cacheThresholdObj->format("Y-m-d H:i:s");
        }


        // Always return from cache full beans.
        $cacheDatasource = $cacheDatasource->applyTransformation(new FilterTransformation([
            new Filter("[[" . $config->getCacheDatasourceParametersField() . "]]", $encodedParameters),
            new Filter("[[" . $config->getCacheDatasourceCachedTimeField() . "]]", $cacheThreshold, Filter::FILTER_TYPE_GREATER_THAN)
        ]));

        return $cacheDatasource->materialise();

    }


}