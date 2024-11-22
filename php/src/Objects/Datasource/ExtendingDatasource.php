<?php

namespace Kinintel\Objects\Datasource;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\Configuration\ExtendingDatasourceConfig;
use Kinintel\ValueObjects\Datasource\TransformationApplication;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

/**
 * Extending datasource - allows for configuration of a datasource
 * with a base datasource and apply transformations
 */
class ExtendingDatasource extends BaseDatasource {

    private DatasourceService $datasourceService;

    /** @var Transformation[]|null */
    private ?array $transformations = null;

    public function __construct(
        ?ExtendingDatasourceConfig $config = null,
        ?DatasourceService $datasourceService = null
    ) {
        parent::__construct($config);
        $this->datasourceService = $datasourceService ?? Container::instance()->get(DatasourceService::class);
    }

    public function setDatasourceService(DatasourceService $datasourceService) {
        $this->datasourceService = $datasourceService;
    }

    /**
     * @param $baseDatasourceKey
     * @param Transformation[] $transformations
     * @param DatasourceService|null $datasourceService
     * @return ExtendingDatasource
     */
    public static function create($baseDatasourceKey, $transformations, ?DatasourceService $datasourceService) {
        $datasource = new ExtendingDatasource(new ExtendingDatasourceConfig($baseDatasourceKey, []), $datasourceService);
        $datasource->transformations = $transformations;
        return $datasource;
    }

    /**
     * We don't actually apply the transformations here, we just add them to a list
     *
     * NOTE: As a consequence, we ignore parameter values in this function!
     *
     * @param Transformation $transformation
     * @param $parameterValues
     * @param $pagingTransformation
     * @return ExtendingDatasource
     * @throws InvalidTransformationConfigException
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null): ExtendingDatasource {
        // Return base datasource with a list of transformations

        /** @var ExtendingDatasourceConfig $config */
        $config = $this->getConfig();

        if (!$this->transformations){
            // Get transformations from config
            $transformations = array_map(
                fn($transformationInstance) => $transformationInstance->returnTransformation(),
                $config->getTransformationInstances()
            );
        } else {
            $transformations = $this->transformations;
        }

        // Add the new one
        $transformations[] = $transformation;

        return ExtendingDatasource::create($config->getBaseDatasourceKey(), $transformations, $this->datasourceService);

    }

    public function materialiseDataset($parameterValues = []) {
        /** @var ExtendingDatasourceConfig $config */
        $config = $this->getConfig();

        // Grab the base datasource
        $baseDatasource = $this->datasourceService
            ->getDataSourceInstanceByKey($config->getBaseDatasourceKey())
            ->returnDataSource();

        $workingDatasource = $baseDatasource;

        // Either we have already applied a transformation and $this->transformations is populated
        // or we have a new Extending datasource and we need get the transformations from config.
        if (!$this->transformations) {
            $this->transformations = array_map(
                fn($transformationInstance) => $transformationInstance->returnTransformation(),
                $config->getTransformationInstances()
            );
        }

        foreach ($this->transformations as $transformation) {
            $workingDatasource = $workingDatasource->applyTransformation(
                $transformation,
                $parameterValues
            );
        }

        // Return materialised working datasource
        return $workingDatasource->materialise($parameterValues);

    }

    /**
     * Get the config class
     *
     * @return string
     */
    public function getConfigClass() {
        return ExtendingDatasourceConfig::class;
    }

    /**
     * Configure without authentication
     *
     * @return false
     */
    public function isAuthenticationRequired() {
        return false;
    }


    /**
     * @return string[]
     */
    public function getSupportedTransformationClasses() {
        return [PagingTransformation::class];
    }

}