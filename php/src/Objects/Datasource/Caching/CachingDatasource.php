<?php


namespace Kinintel\Objects\Datasource\Caching;


use Kinikit\Core\DependencyInjection\Container;
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
        $cacheThreshold = new \DateTime();
        if ($config->getCacheExpiryDays()) $cacheThreshold->sub(new \DateInterval("P" . $config->getCacheExpiryDays() . "D"));
        if ($config->getCacheExpiryHours()) $cacheThreshold->sub(new \DateInterval("PT" . $config->getCacheExpiryHours() . "H"));
        $cacheThreshold = $cacheThreshold->format("Y-m-d H:i:s");

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
        if (!$cacheCheckDataset->nextDataItem()) {

            // Materialise the source data set using parameters
            $sourceDatasource = $sourceDatasourceInstance->returnDataSource();
            $sourceDataset = $sourceDatasource->materialise($parameterValues);

            // Create merged fields ready for insert
            $fields = array_merge([
                new Field($config->getCacheDatasourceParametersField()),
                new Field($config->getCacheDatasourceCachedTimeField())
            ], $sourceDataset->getColumns());

            // Insert in batches of 50
            $batch = [];
            while ($sourceItem = $sourceDataset->nextDataItem()) {
                $batch[] = array_merge([$config->getCacheDatasourceParametersField() => $encodedParameters,
                    $config->getCacheDatasourceCachedTimeField() => $now], $sourceItem);
                if (sizeof($batch) == 50) {
                    $cacheDatasource->update(new ArrayTabularDataset($fields, $batch));
                    $batch = [];
                }
            }
            if (sizeof($batch)) {
                $cacheDatasource->update(new ArrayTabularDataset($fields, $batch));
            }

        }


        // Always return from cache full beans.
        $cacheDatasource = $cacheDatasource->applyTransformation(new FilterTransformation([
            new Filter("[[" . $config->getCacheDatasourceParametersField() . "]]", $encodedParameters),
            new Filter("[[" . $config->getCacheDatasourceCachedTimeField() . "]]", $cacheThreshold, Filter::FILTER_TYPE_GREATER_THAN)
        ]));

        return $cacheDatasource->materialise();

    }


}