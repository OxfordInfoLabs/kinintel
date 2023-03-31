<?php


namespace Kinintel\Traits\Controller\Admin;


use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\Workflow\Task\LongRunning\StoredLongRunningTaskSummary;
use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTaskService;
use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSummary;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetEvaluatorLongRunningTask;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Util\SQLClauseSanitiser;

trait Dataset {

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * @var LongRunningTaskService
     */
    private $longRunningTaskService;


    /**
     * @var SQLClauseSanitiser
     */
    private $sqlClauseSanitiser;

    /**
     * Dataset constructor.
     *
     * @param DatasetService $datasetService
     * @param LongRunningTaskService $longRunningTaskService
     * @param SQLClauseSanitiser $sqlClauseSanitiser
     */
    public function __construct($datasetService, $longRunningTaskService, $sqlClauseSanitiser) {
        $this->datasetService = $datasetService;
        $this->longRunningTaskService = $longRunningTaskService;
        $this->sqlClauseSanitiser = $sqlClauseSanitiser;
    }


    /**
     * Get a dataset instance summary by id
     *
     * @http GET /$id
     *
     * @param $id
     * @return DatasetInstanceSummary
     */
    public function getDatasetInstance($id) {
        return $this->datasetService->getDataSetInstance($id);
    }


    /**
     * Get an extended dataset instance summary by id
     *
     * @http GET /extended/$id
     *
     * @param $id
     * @return DatasetInstanceSummary
     */
    public function getExtendedDatasetInstance($id) {
        return $this->datasetService->getExtendedDatasetInstance($id);
    }


    /**
     * Filter dataset instances, optionally by title, project key, tags and limited by offset and limit.
     *
     * @http GET /
     *
     * @param string $filterString
     * @param string $categories
     * @param string $projectKey
     * @param string $tags
     * @param int $offset
     * @param int $limit
     *
     * @return \Kinintel\Objects\Dataset\DatasetInstanceSearchResult[]
     */
    public function filterDatasetInstances($filterString = "", $categories = "", $accountId = 0, $offset = 0, $limit = 10) {
        $categories = $categories ? explode(",", $categories) : [];
        return $this->datasetService->filterDataSetInstances($filterString, $categories, [], null, $offset, $limit, is_numeric($accountId) ? $accountId : null);
    }


    /**
     * Filter in use dataset instance categories optionally for a project and tags
     *
     * @http GET /inUseCategories
     *
     * @param string $projectKey
     * @param string $tags
     *
     * @return CategorySummary[]
     */
    public function getInUseDatasetInstanceCategories($projectKey = null, $tags = "", $accountId = 0) {
        return $this->datasetService->getInUseDatasetInstanceCategories($tags, $projectKey, $accountId);
    }


    /**
     * Save a data set instance object
     *
     * @http POST
     * @unsanitise dataSetInstanceSummary
     *
     * @param DatasetInstanceSummary $dataSetInstanceSummary
     * @param string $projectKey
     */
    public function saveDatasetInstance($dataSetInstanceSummary, $projectKey = null, $accountId = 0) {
        $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, $projectKey, is_numeric($accountId) ? $accountId : null);
    }


    /**
     * Update meta data for a dataset instance
     *
     * @http PATCH
     * @unsanitise datasetInstanceSearchResult
     *
     * @param DatasetInstanceSearchResult $datasetInstanceSearchResult
     */
    public function updateDatasetInstanceMetaData($datasetInstanceSearchResult) {
        $this->datasetService->updateDataSetMetaData($datasetInstanceSearchResult);
    }


    /**
     * Remove a dataset instance by id
     *
     * @http DELETE /$id
     *
     * @param $id
     */
    public function removeDatasetInstance($id) {
        $this->datasetService->removeDataSetInstance($id);
    }


    /**
     * Retrieve results for the supplied tracking key
     *
     * @http GET /results/$trackingKey
     *
     * @param $trackingKey
     *
     * @return StoredLongRunningTaskSummary
     */
    public function retrieveDatasetResults($trackingKey) {
        return $this->longRunningTaskService->getStoredTaskByTaskKey($trackingKey);
    }

    /**
     * Get the evaluated parameters for the supplied dataset instance by id.
     * The array of transformation instances can be supplied as payload.
     *
     * @http POST /parameters
     *
     * @param DatasetInstanceSummary $datasetInstanceSummary
     */
    public function getEvaluatedParameters($datasetInstanceSummary) {
        return $this->datasetService->getEvaluatedParameters($datasetInstanceSummary);
    }


    /**
     * Evaluate a dataset and return a dataset
     *
     * @http POST /evaluate
     *
     * @unsanitise $datasetInstanceSummary
     *
     * @param DatasetInstanceSummary $datasetInstanceSummary
     * @param string $offset
     * @param string $limit
     * @param string $trackingKey
     *
     * @return \Kinintel\Objects\Dataset\Dataset
     */
    public function evaluateDataset($datasetInstanceSummary, $offset = 0, $limit = 25, $trackingKey = null) {

        if (!$trackingKey) {
            $trackingKey = date("U") . StringUtils::generateRandomString(5);
        }

        // Create a long running task for this dataset
        $longRunningTask = new DatasetEvaluatorLongRunningTask($this->datasetService, $datasetInstanceSummary, $offset, $limit);

        // Start the task and return results
        return $this->longRunningTaskService->startTask("Dataset", $longRunningTask, $trackingKey, null, 0);


    }


    /**
     * Filter snapshot profiles optionally by a string, project and tags
     *
     * @http GET /snapshotprofile
     *
     * @param string $filterString
     * @param string $projectKey
     * @param string $tags
     * @param int $offset
     * @param int $limit
     * @return \Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSearchResult[]
     */
    public function filterSnapshotProfiles($filterString = "", $projectKey = null, $tags = "", $offset = 0, $limit = 10) {
        $tags = $tags ? explode(",", $tags) : [];
        return $this->datasetService->filterSnapshotProfiles($filterString, $tags, $projectKey, $offset, $limit);
    }

    /**
     * List snapshot profiles for dataset instance by instance id
     *
     * @http GET /snapshotprofile/$datasetInstanceId
     *
     * @param $datasetInstanceId
     */
    public function listSnapshotProfilesForDataSetInstance($datasetInstanceId) {
        return $this->datasetService->listSnapshotProfilesForDataSetInstance($datasetInstanceId);
    }


    /**
     * Save a snapshot profile for an instance
     *
     * @http POST /snapshotprofile/$datasetInstanceId
     *
     * @param DatasetInstanceSnapshotProfileSummary $snapshotProfileSummary
     * @param $datasetInstanceId
     */
    public function saveSnapshotProfile($datasetInstanceId, $snapshotProfileSummary) {
        $this->datasetService->saveSnapshotProfile($snapshotProfileSummary, $datasetInstanceId);
    }


    /**
     * Remove a snapshot profile for an instance
     *
     * @http DELETE /snapshotprofile/$datasetInstanceId
     *
     * @param $datasetInstanceId
     * @param $snapshotProfileId
     */
    public function removeSnapshotProfile($snapshotProfileId, $datasetInstanceId) {
        $this->datasetService->removeSnapshotProfile($datasetInstanceId, $snapshotProfileId);
    }

    /**
     * Get the installed whitelisted SQL functions
     *
     * @http GET /whitelistedsqlfunctions
     *
     * @return array
     */
    public function getInstalledWhitelistedSQLFunctions() {
        return $this->sqlClauseSanitiser->getWhitelistedFunctions();
    }


}
