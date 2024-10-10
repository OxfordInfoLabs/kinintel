<?php


namespace Kinintel\Objects\Datasource\Caching;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Validation\ValidationException;
use Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Caching\CachingDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\Sort;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class CachingDatasource extends BaseDatasource {

    private DatasourceService $datasourceService;
    private DatasetService $datasetService;

    /**
     * @var Transformation[]
     */
    private $appliedTransformations = [];


    public function __construct($config = null, $authenticationCredentials = null, $validator = null) {
        parent::__construct($config, $authenticationCredentials, $validator);
        $this->datasourceService = Container::instance()->get(DatasourceService::class);
        $this->datasetService = Container::instance()->get(DatasetService::class);
    }

    public function getConfigClass(): string {
        return CachingDatasourceConfig::class;
    }

    /**
     * No directly supported transformations here
     *
     * @return class-string[]
     */
    public function getSupportedTransformationClasses() {
        return [
            PagingTransformation::class,
            FilterTransformation::class,
            MultiSortTransformation::class,
            SummariseTransformation::class,
            JoinTransformation::class
        ];
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
     * @param null $pagingTransformation //todo Type??
     * @return BaseDatasource|void
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {
        $this->appliedTransformations[] = $transformation;
        return $this;
    }

    /**
     * @param DatasourceService $datasourceService
     */
    public function setDatasourceService($datasourceService) {
        $this->datasourceService = $datasourceService;
    }

    public function setDatasetService($datasetService) {
        $this->datasetService = $datasetService;
    }


    /**
     * @param array $parameterValues
     * @return Dataset
     * @throws ValidationException
     * @throws MissingDatasourceAuthenticationCredentialsException
     * @throws UnsupportedDatasetException
     */
    public function materialiseDataset($parameterValues = []) {


        /**
         * @var CachingDatasourceConfig $config
         */
        $config = $this->getConfig();

        $cacheDatasourceInstance = $config->getCacheDatasource() ?? $this->datasourceService->getDataSourceInstanceByKey($config->getCacheDatasourceKey());

        // Check the cache by doing a very limited query based upon
        // cache limits
        $now = (new \DateTime())->format("Y-m-d H:i:s");
        $cacheThresholdObj = new \DateTime();
        if ($config->getCacheExpiryDays()) $cacheThresholdObj->sub(new \DateInterval("P" . $config->getCacheExpiryDays() . "D"));
        if ($config->getCacheExpiryHours()) $cacheThresholdObj->sub(new \DateInterval("PT" . $config->getCacheExpiryHours() . "H"));
        $cacheThreshold = $cacheThresholdObj->format("Y-m-d H:i:s");

        // Encode parameters
        $sourceParameterValues = [];
        $instanceParams = $this->getInstanceInfo()->getParameters();
        foreach ($instanceParams ?? [] as $parameter) {
            if (isset($parameterValues[$parameter->getName()]))
                $sourceParameterValues[$parameter->getName()] = $parameterValues[$parameter->getName()];
        }

        if ($config->isHashing()) {
            $encodedParameters = json_encode(["hash" => md5(json_encode($sourceParameterValues))]);
        } else {
            $encodedParameters = json_encode($sourceParameterValues);
        }


        Logger::log("Starting first cache check");

        // Get a cache check data item
        $checkDataItem = $this->getCacheCheckDataItem($cacheDatasourceInstance, $config, $encodedParameters);

        Logger::log("Ended first cache check");


        // Grab a new data source for return results
        $cacheDatasource = $cacheDatasourceInstance->returnDataSource();

        // If no previously cached results or we need new ones, go get them
        $noSourceResults = false;
        if (!$checkDataItem || ($checkDataItem[$config->getCacheDatasourceCachedTimeField()] < $cacheThreshold)) {

            Logger::log("Getting new Object");

            $sourceParams = $parameterValues ?? [];

            // If we are in incremental or update mode, we need to know when our last cache update was so we can pass this as a parameter to
            // source
            if ($config->getCacheMode() !== CachingDatasourceConfig::CACHE_MODE_COMPLETE) {
                if ($checkDataItem) {
                    $lastUpdateDate = date_create_from_format("Y-m-d H:i:s", $checkDataItem[$config->getCacheDatasourceCachedTimeField()]);
                    $lastCacheTimestamp = $lastUpdateDate ? $lastUpdateDate->format("U") : 0;
                } else {
                    $lastCacheTimestamp = 0;
                }

                $sourceParams["lastCacheTimestamp"] = $lastCacheTimestamp;
                $sourceParams["lastCacheOffset"] = date("U") - $lastCacheTimestamp;
            }

            $noSourceResults = true;

            if ($config->getSourceDatasetId()) {
                $sourceDataset = $this->datasetService->getEvaluatedDataSetForDataSetInstanceById($config->getSourceDatasetId(), $sourceParams);
            } else {
                $sourceDatasourceInstance = $config->getSourceDatasource() ?? $this->datasourceService->getDataSourceInstanceByKey($config->getSourceDatasourceKey());

                $sourceDatasource = $sourceDatasourceInstance->returnDataSource();
                $sourceDataset = $sourceDatasource->materialise($sourceParams);
            }

            Logger::log("Got new Object");

            // Create merged fields ready for insert
            $targetFields = [
                new Field($config->getCacheDatasourceParametersField()),
                new Field($config->getCacheDatasourceCachedTimeField())
            ];

            // Recreate target fields to ensure we remove any mapping rules to avoid strangeness.
            foreach ($sourceDataset->getColumns() as $column) {
                $targetFields[] = new Field($column->getName(), $column->getTitle());
            }


            // Set update mode according to cache mode
            $updateMode = $config->getCacheMode() == CachingDatasourceConfig::CACHE_MODE_UPDATE ? UpdatableDatasource::UPDATE_MODE_REPLACE : UpdatableDatasource::UPDATE_MODE_REPLACE;

            // Perform a final cache check before insert to ensure that an item hasn't been inserted while we
            // have been processing
            $checkDataItem = $this->getCacheCheckDataItem($cacheDatasourceInstance, $config, $encodedParameters);

            // Only proceed with the insert if nothing changed in the meanwhile.
            if (!$checkDataItem || ($checkDataItem[$config->getCacheDatasourceCachedTimeField()] < $cacheThreshold)) {

                // Insert in batches of 50
                $batch = [];
                while ($sourceItem = $sourceDataset->nextDataItem()) {

                    $noSourceResults = false;
                    $batch[] = array_merge([$config->getCacheDatasourceParametersField() => $encodedParameters,
                        $config->getCacheDatasourceCachedTimeField() => $now], $sourceItem);
                    if (sizeof($batch) == 50) {
                        $cacheDatasource->update(new ArrayTabularDataset($targetFields, $batch), $updateMode);
                        $batch = [];
                    }
                }
                if (sizeof($batch)) {
                    $cacheDatasource->update(new ArrayTabularDataset($targetFields, $batch), $updateMode);
                }

            }
        }


        // if no source results and we have a fallback to older rule in place, move the cache threshold back to the
        // previous timeframe to allow previous results to flow through if they exist
        if ($noSourceResults && $config->isFallbackToOlder()) {
            if ($config->getCacheExpiryDays()) $cacheThresholdObj->sub(new \DateInterval("P" . $config->getCacheExpiryDays() . "D"));
            if ($config->getCacheExpiryHours()) $cacheThresholdObj->sub(new \DateInterval("PT" . $config->getCacheExpiryHours() . "H"));
            $cacheThreshold = $cacheThresholdObj->format("Y-m-d H:i:s");
        }


        // Return from cache with filters
        $filters = [
            new Filter("[[" . $config->getCacheDatasourceParametersField() . "]]", $encodedParameters)
        ];

        // If the cache mode is complete we are only interested in the last data set
        if ($config->getCacheMode() == CachingDatasourceConfig::CACHE_MODE_COMPLETE) {
            $filters[] = new Filter("[[" . $config->getCacheDatasourceCachedTimeField() . "]]", $cacheThreshold, Filter::FILTER_TYPE_GREATER_THAN);
        }

        $cacheDatasource = $cacheDatasource->applyTransformation(new FilterTransformation($filters));

        // Apply additional transformations before materialisation
        foreach ($this->appliedTransformations as $appliedTransformation) {
            $cacheDatasource = $cacheDatasource->applyTransformation($appliedTransformation, $parameterValues);
        }

        Logger::log("Starting cache materialise");

        $result = $cacheDatasource->materialise($parameterValues);

        Logger::log("Ended cache materialise");

        return $result;
    }

    /**
     * Get the cache item which matches the encoded parameters, or return null if there's nothing in the cache
     *
     * @param DatasourceInstance $cacheDatasourceInstance
     * @param CachingDatasourceConfig $config
     * @param $encodedParameters
     * @return mixed
     * @throws \Kinikit\Core\Validation\ValidationException
     * @throws \Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException
     */
    private function getCacheCheckDataItem(DatasourceInstance $cacheDatasourceInstance, CachingDatasourceConfig $config, $encodedParameters) {

        // Do a limited check on cache data source
        $cacheCheckDatasource = $cacheDatasourceInstance->returnDataSource();
        $cacheCheckDatasource = $cacheCheckDatasource->applyTransformation(new FilterTransformation([
            new Filter("[[" . $config->getCacheDatasourceParametersField() . "]]", $encodedParameters),
        ]));
        $cacheCheckDatasource = $cacheCheckDatasource->applyTransformation(new MultiSortTransformation([
            new Sort($config->getCacheDatasourceCachedTimeField(), "DESC")
        ]));
        $cacheCheckDatasource = $cacheCheckDatasource->applyTransformation(new PagingTransformation(1, 0));

        // Materialise the cache data source
        $cacheCheckDataset = $cacheCheckDatasource->materialise();
        $checkDataItem = $cacheCheckDataset->nextDataItem();
        return $checkDataItem;
    }


}