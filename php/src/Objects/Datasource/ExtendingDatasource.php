<?php

namespace Kinintel\Objects\Datasource;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\Configuration\ExtendingDatasourceConfig;

/**
 * Extending datasource - allows for configuration of a datasource
 * with a base datasource and apply transformations
 */
class ExtendingDatasource extends BaseDatasource {

    private DatasourceService $datasourceService;
    private ?Datasource $workingDatasource = null;
    private bool $transformationsProcessed = false;

    /**
     * @param ExtendingDatasourceConfig $config
     * @param DatasourceService $datasourceService
     */
    public function __construct($config = null, $datasourceService = null) {
        parent::__construct($config);
        $this->datasourceService = $datasourceService ?? Container::instance()->get(DatasourceService::class);
    }

    /**
     * @param DatasourceService $datasourceService
     */
    public function setDatasourceService($datasourceService) {
        $this->datasourceService = $datasourceService;
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
        // TODO: Implement getSupportedTransformationClasses() method.
    }


    /**
     * Apply transformations
     *
     * @param $transformation
     * @param $parameterValues
     * @param $pagingTransformation
     * @return Datasource
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {

        $this->processTransformations($parameterValues);
        $this->workingDatasource = $this->workingDatasource->applyTransformation($transformation, $parameterValues);
        return $this->workingDatasource;
    }

    public function materialiseDataset($parameterValues = []) {


        // Process any transformations
        $this->processTransformations($parameterValues);

        // Return materialised working datasource
        return $this->workingDatasource->materialise($parameterValues);

    }

    /**
     * Process the transformations if required
     *
     * @return void
     * @throws InvalidTransformationConfigException
     */
    private function processTransformations($parameterValues) {

        if (!$this->transformationsProcessed) {
            /**
             * @var ExtendingDatasourceConfig $config
             */
            $config = $this->getConfig();

            // Grab the datasource
            $this->getWorkingDatasource();

            foreach ($config->getTransformationInstances() ?? [] as $transformationInstance) {
                $this->workingDatasource = $this->workingDatasource->applyTransformation($transformationInstance->returnTransformation(), $parameterValues);
            }
        }
        $this->transformationsProcessed = true;

    }


    private function getWorkingDatasource() {
        if (!$this->workingDatasource) {

            /**
             * @var ExtendingDatasourceConfig $config
             */
            $config = $this->getConfig();

            // Grab the base datasource instance
            $baseDatasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($config->getBaseDatasourceKey());

            // Pull out the base datasource
            $this->workingDatasource = $baseDatasourceInstance->returnDataSource();

        }
        return $this->workingDatasource;
    }


}