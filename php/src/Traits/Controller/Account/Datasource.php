<?php


namespace Kinintel\Traits\Controller\Account;

use Cassandra\Custom;
use Kiniauth\Objects\Account\Account;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use Kinikit\MVC\Request\FileUpload;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSummary;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\CustomDatasourceService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\EvaluatedDataSource;
use Kinintel\ValueObjects\Datasource\Update\DatasourceConfigUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * Datasource trait for account level access to datasources
 *
 * Class Datasource
 * @package Kinintel\Traits\Controller\Account
 */
trait Datasource {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var CustomDatasourceService
     */
    private $customDatasourceService;

    /**
     * Datasource constructor.
     * @param DatasourceService $datasourceService
     * @param CustomDatasourceService $customDatasourceService
     */
    public function __construct($datasourceService, $customDatasourceService) {
        $this->datasourceService = $datasourceService;
        $this->customDatasourceService = $customDatasourceService;
    }


    /**
     * Get a datasource by key
     *
     * @http GET /$key
     *
     * @param $key
     * @return DatasourceInstanceSummary
     */
    public function getDatasourceInstance($key) {
        return $this->datasourceService->getDataSourceInstanceByKey($key);
    }


    /**
     * @http PUT /$key
     *
     * @param $key
     * @param DatasourceConfigUpdate $documentDatasourceConfig
     *
     * @return string
     */
    public function updateDatasourceInstance($key, $documentDatasourceConfig) {
        $instance = $this->datasourceService->getDataSourceInstanceByKey($key);
        $instance->setConfig($documentDatasourceConfig->getConfig());
        $instance->setTitle($documentDatasourceConfig->getTitle());
        return $this->datasourceService->saveDataSourceInstance($instance)->getKey();
    }

    /**
     * Filter datasource instances
     *
     * @http GET /
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     */
    public function filterDatasourceInstances($filterString = "", $limit = 10, $offset = 0, $projectKey = null) {
        return $this->datasourceService->filterDatasourceInstances($filterString, $limit, $offset, $projectKey);
    }


    /**
     * Get the evaluated parameters for the supplied instance by key.
     * The array of transformation instances can be supplied as payload.
     *
     * @http GET /parameters/$key
     *
     * @param string $key
     */
    public function getEvaluatedParameters($key) {
        return $this->datasourceService->getEvaluatedParameters($key);
    }

    /**
     * Evaluate a datasource and return a dataset
     *
     * @http POST /evaluate
     *
     * @param EvaluatedDataSource $evaluatedDataSource
     * @return \Kinintel\Objects\Dataset\Dataset
     */
    public function evaluateDatasource($evaluatedDataSource) {
        $result = $this->datasourceService->getEvaluatedDataSource($evaluatedDataSource->getKey(), $evaluatedDataSource->getParameterValues(), $evaluatedDataSource->getTransformationInstances(),
            $evaluatedDataSource->getOffset() ?? 0, $evaluatedDataSource->getLimit() ?? 25);
        return $result;
    }


    /**
     * Create a new custom datasource instance.  Return the datasource key
     *
     * @http POST /custom
     *
     * @param DatasourceUpdateWithStructure $datasourceUpdate
     * @param string $projectKey
     *
     * @return string
     */
    public function createCustomDatasourceInstance($datasourceUpdate, $projectKey = null) {
        return $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, $projectKey);
    }


    /**
     * Update a custom datasource instance with the supplied data and optionally structure
     *
     *
     * @http PUT /custom/$datasourceInstanceKey
     *
     * @param string $datasourceInstanceKey
     * @param DatasourceUpdateWithStructure $datasourceUpdate
     */
    public function updateCustomDatasourceInstance($datasourceInstanceKey, $datasourceUpdate) {
        $this->datasourceService->updateDatasourceInstance($datasourceInstanceKey, $datasourceUpdate);
    }


    /**
     * @http POST /document
     *
     * @param DatasourceConfigUpdate $documentDatasourceConfig
     * @param null $projectKey
     *
     * @return integer
     */
    public function createDocumentDatasourceInstance($documentDatasourceConfig, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->customDatasourceService->createDocumentDatasourceInstance($documentDatasourceConfig, $projectKey, $accountId);
    }


    /**
     * @http POST /document/upload/$datasourceInstanceKey
     *
     * @param string $datasourceInstanceKey
     * @param FileUpload[] $uploadedFiles
     * @return void
     */
    public function uploadDocumentsToDocumentDatasource($datasourceInstanceKey, $uploadedFiles) {
        $this->customDatasourceService->uploadDocumentsToDocumentDatasource($datasourceInstanceKey, $uploadedFiles);
    }


}
