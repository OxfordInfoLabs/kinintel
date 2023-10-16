<?php

namespace Kinintel\Objects\Datasource\Union;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\Configuration\Union\UnionDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Combine\CombineTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class UnionDatasource extends BaseDatasource {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    public function __construct($config = null, $authenticationCredentials = null, $validator = null) {
        parent::__construct($config, $authenticationCredentials, $validator);
        $this->datasourceService = Container::instance()->get(DatasourceService::class);
    }

    /**
     * @return string[]
     */
    public function getSupportedTransformationClasses() {
        return [PagingTransformation::class];
    }

    /**
     * Disable authentication
     *
     * @return false
     */
    public function isAuthenticationRequired() {
        return false;
    }

    public function getConfigClass() {
        return UnionDatasourceConfig::class;
    }


    /**
     * @param Transformation $transformation
     * @param array $parameterValues
     * @param null $pagingTransformation
     * @return BaseDatasource|void
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {
        return $this;
    }

    /**
     * Materialise dataset
     *
     * @param array $parameterValues
     * @return Dataset|void
     */
    public function materialiseDataset($parameterValues = []) {

        /**
         * @var UnionDatasourceConfig $config
         */
        $config = $this->getConfig();

        $datasourceMappings = $config->getSourceDatasources();

        $initialDatasourceInstance = $this->datasourceService->getDataSourceInstanceByKey(array_shift($datasourceMappings)->getKey());
        $initialDatasource = $initialDatasourceInstance->returnDataSource();

        foreach ($datasourceMappings as $datasourceMapping) {
            $fieldMappings = array_combine($datasourceMapping->getColumns(), $config->getTargetColumns());
            $initialDatasource->applyTransformation(new CombineTransformation($datasourceMapping->getKey(), null, CombineTransformation::COMBINE_TYPE_UNION, $fieldMappings));
        }

        return $initialDatasource->materialise($parameterValues);

    }

    /**
     * @param DatasourceService $datasourceService
     * @return void
     */
    public function setDatasourceService($datasourceService) {
        $this->datasourceService = $datasourceService;
    }
}