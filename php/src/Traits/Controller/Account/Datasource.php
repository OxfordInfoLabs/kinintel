<?php


namespace Kinintel\Traits\Controller\Account;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\LongRunning\StoredLongRunningTaskSummary;
use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTaskService;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\StringUtils;
use Kinikit\MVC\Request\FileUpload;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Datasource\DatasourceInstanceSummary;
use Kinintel\Services\Datasource\CustomDatasourceService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Datasource\DocumentUploadLongRunningTask;
use Kinintel\ValueObjects\Datasource\EvaluatedDataSource;
use Kinintel\ValueObjects\Datasource\Update\DatasourceConfigUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

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
     * @var LongRunningTaskService
     */
    private $longRunningTaskService;

    /**
     * Datasource constructor.
     * @param DatasourceService $datasourceService
     * @param CustomDatasourceService $customDatasourceService
     * @param LongRunningTaskService $longRunningTaskService
     */
    public function __construct($datasourceService, $customDatasourceService, $longRunningTaskService) {
        $this->datasourceService = $datasourceService;
        $this->customDatasourceService = $customDatasourceService;
        $this->longRunningTaskService = $longRunningTaskService;
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
     * @unsanitise
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
     * @http DELETE /$key
     *
     * @param $key
     */
    public function deleteDatasourceInstance($key) {
        $this->datasourceService->removeDatasourceInstance($key);
    }


    /**
     * Filter datasource instances
     *
     * @http GET /
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     *
     * @hasPrivilege PROJECT:customdatasourceaccess($projectKey)
     */
    public function filterDatasourceInstances($filterString = "", $limit = 10, $offset = 0, $projectKey = null) {
        return $this->datasourceService->filterDatasourceInstances($filterString, $limit, $offset, false, $projectKey);
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
        $result = $this->datasourceService->getEvaluatedDataSourceByInstanceKey($evaluatedDataSource->getKey(), $evaluatedDataSource->getParameterValues(), $evaluatedDataSource->getTransformationInstances(),
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
     * @param string $datasourceKey
     *
     * @return string
     *
     * @hasPrivilege PROJECT:customdatasourcemanage($projectKey)
     */
    public function createCustomDatasourceInstance($datasourceUpdate, $projectKey = null, $datasourceKey = null) {
        return $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, $datasourceKey, $projectKey);
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
        $this->datasourceService->updateDatasourceInstanceByKey($datasourceInstanceKey, $datasourceUpdate);
    }


    /**
     * Update a custom datasource instance with the supplied data and optionally structure
     *
     *
     * @http PATCH /custom/$datasourceInstanceKey
     *
     * @param string $datasourceInstanceKey
     * @param DatasourceUpdate $datasourceUpdate
     * @unsanitise datasourceUpdate
     */
    public function populateCustomDatasourceInstance($datasourceInstanceKey, $datasourceUpdate) {
        $this->datasourceService->updateDatasourceInstanceByKey($datasourceInstanceKey, $datasourceUpdate);
    }


    /**
     * @http POST /document
     *
     * @param DatasourceConfigUpdate $documentDatasourceConfig
     * @param null $projectKey
     *
     * @return integer
     *
     * @hasPrivilege PROJECT:customdatasourcemanage($projectKey)
     */
    public function createDocumentDatasourceInstance($documentDatasourceConfig, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->customDatasourceService->createDocumentDatasourceInstance($documentDatasourceConfig, $projectKey, $accountId);
    }


    /**
     * @http POST /document/upload/$datasourceInstanceKey
     *
     * @param string $datasourceInstanceKey
     * @param FileUpload[] $uploadedFiles
     * @param string $trackingKey
     * @return void
     */
    public function uploadDocumentsToDocumentDatasource($datasourceInstanceKey, $uploadedFiles, $trackingKey = null) {

        if (!$trackingKey) {
            $trackingKey = date("U") . StringUtils::generateRandomString(5);
        }

        // Create a long running task for this dataset
        $longRunningTask = new DocumentUploadLongRunningTask($this->customDatasourceService, $datasourceInstanceKey, $uploadedFiles);

        // Start the task and return results
        return $this->longRunningTaskService->startTask("DocumentUpload", $longRunningTask, $trackingKey);
    }

    /**
     * @http GET /document/upload/tracking
     *
     * @param $trackingKey
     * @return StoredLongRunningTaskSummary
     * @throws ObjectNotFoundException
     */
    public function retrieveUploadResults($trackingKey) {
        return $this->longRunningTaskService->getStoredTaskByTaskKey($trackingKey);
    }

}
