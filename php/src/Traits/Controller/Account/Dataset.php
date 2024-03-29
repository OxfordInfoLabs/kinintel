<?php


namespace Kinintel\Traits\Controller\Account;

use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\Workflow\Task\LongRunning\StoredLongRunningTaskSummary;
use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTaskService;
use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSummary;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetEvaluatorLongRunningTask;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Util\SQLClauseSanitiser;
use Kinintel\ValueObjects\Dataset\DatasetInstanceEvaluationDescriptor;
use Kinintel\ValueObjects\Dataset\ExportDataset;

/**
 * Dataset service, acts on both in process and saved datasets
 *
 * Trait Dataset
 * @package Kinintel\Traits\Controller\Account
 */
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
     * @param string $projectKey
     * @param string $categories
     * @param string $tags
     * @param int $offset
     * @param int $limit
     *
     * @return \Kinintel\Objects\Dataset\DatasetInstanceSearchResult[]
     */
    public function filterDatasetInstances($filterString = "", $categories = "", $projectKey = null, $tags = "", $offset = 0, $limit = 10) {
        $tags = $tags ? explode(",", $tags) : [];
        $categories = $categories ? explode(",", $categories) : [];
        return $this->datasetService->filterDataSetInstances($filterString, $categories, $tags, $projectKey, $offset, $limit);
    }


    /**
     * Filter in use dataset categories optionally for a project and tags
     *
     * @http GET /inUseCategories
     *
     * @param string $projectKey
     * @param string $tags
     *
     * @return CategorySummary[]
     */
    public function getInUseDatasetInstanceCategories($projectKey = null, $tags = "") {
        return $this->datasetService->getInUseDatasetInstanceCategories($tags, $projectKey);
    }

    /**
     * Save a data set instance object
     *
     * @http POST
     *
     * @unsanitise dataSetInstanceSummary
     * @param DatasetInstanceSummary $dataSetInstanceSummary
     * @param string $projectKey
     */
    public function saveDatasetInstance($dataSetInstanceSummary, $projectKey = null) {
        $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, $projectKey);
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
     * @param integer $offset
     * @param integer $limit
     * @param string $trackingKey
     * @param string $projectKey
     *
     * @return \Kinintel\Objects\Dataset\Dataset
     */
    public function evaluateDataset($datasetInstanceSummary, $offset = 0, $limit = 25, $trackingKey = null, $projectKey = null) {

        if (!$trackingKey) {
            $trackingKey = date("U") . StringUtils::generateRandomString(5);
        }

        // Create a long running task for this dataset
        $longRunningTask = new DatasetEvaluatorLongRunningTask($this->datasetService, $datasetInstanceSummary, $offset, $limit);

        // Start the task and return results
        return $this->longRunningTaskService->startTask("Dataset", $longRunningTask, $trackingKey, $projectKey);
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
     * Export a dataset, streaming results directly
     *
     * @http POST /export
     *
     * @unsanitise $exportDataset
     *
     * @param ExportDataset $exportDataset
     */
    public function exportDataset($exportDataset) {

        return $this->datasetService->exportDatasetInstance($exportDataset->getDataSetInstanceSummary(),
            $exportDataset->getExporterKey(), $exportDataset->getExporterConfiguration(),
            $exportDataset->getParameterValues(), $exportDataset->getTransformationInstances(),
            $exportDataset->getOffset(), $exportDataset->getLimit());
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
     *
     * @hasPrivilege PROJECT:snapshotaccess($projectKey)
     *
     * @return \Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSearchResult[]
     */
    public function filterSnapshotProfiles($filterString = "", $projectKey = null, $tags = "", $offset = 0, $limit = 10) {
        $tags = $tags ? explode(",", $tags) : [];
        return $this->datasetService->filterSnapshotProfiles($filterString, $tags, $projectKey, $offset, $limit);
    }

    /**
     * Get a snapshot profile by ID
     *
     * @http GET /snapshotprofile/$profileId
     *
     * @param $profileId
     * @return mixed
     */
    public function getSnapshotProfile($profileId) {
        return $this->datasetService->getSnapshotProfile($profileId);
    }

    /**
     * List snapshot profiles for dataset instance by instance id
     *
     * @http GET /snapshotprofile/dataset/$datasetInstanceId
     *
     * @param $datasetInstanceId
     *
     * @referenceParameter $datasetInstance DatasetInstance($datasetInstanceId)
     * @hasPrivilege PROJECT:snapshotaccess($datasetInstance.projectKey)
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
     *
     * @referenceParameter $datasetInstance DatasetInstance($datasetInstanceId)
     * @hasPrivilege PROJECT:snapshotaccess($datasetInstance.projectKey)
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
     *
     * @referenceParameter $datasetInstance DatasetInstance($datasetInstanceId)
     * @hasPrivilege PROJECT:snapshotaccess($datasetInstance.projectKey)
     */
    public function removeSnapshotProfile($snapshotProfileId, $datasetInstanceId) {
        $this->datasetService->removeSnapshotProfile($datasetInstanceId, $snapshotProfileId);
    }

    /**
     * Trigger an adhoc snapshot
     *
     * @http PATCH /snapshotprofile/$datasetInstanceId
     *
     * @param $datasetInstanceId
     * @param $snapshotProfileId
     *
     * @referenceParameter $datasetInstance DatasetInstance($datasetInstanceId)
     * @hasPrivilege PROJECT:snapshotaccess($datasetInstance.projectKey)
     *
     */
    public function triggerSnapshot($datasetInstanceId, $snapshotProfileId) {
        $this->datasetService->triggerSnapshot($datasetInstanceId, $snapshotProfileId);
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
