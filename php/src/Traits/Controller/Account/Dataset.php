<?php


namespace Kinintel\Traits\Controller\Account;

use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Objects\Workflow\Task\LongRunning\StoredLongRunningTaskSummary;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Security\ObjectScopeAccessService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTaskService;
use Kiniauth\ValueObjects\Security\ScopeAccessGroup;
use Kiniauth\ValueObjects\Security\ScopeAccessItem;
use Kinikit\Core\Logging\Logger;
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
     * @var ObjectScopeAccessService
     */
    private $objectScopeAccessService;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * @var SecurityService
     */
    private $securityService;


    /**
     * Dataset constructor.
     *
     * @param DatasetService $datasetService
     * @param LongRunningTaskService $longRunningTaskService
     * @param SQLClauseSanitiser $sqlClauseSanitiser
     * @param ObjectScopeAccessService $objectScopeAccessService
     * @param AccountService $accountService
     * @param SecurityService $securityService
     */
    public function __construct($datasetService, $longRunningTaskService, $sqlClauseSanitiser, $objectScopeAccessService, $accountService, $securityService) {
        $this->datasetService = $datasetService;
        $this->longRunningTaskService = $longRunningTaskService;
        $this->sqlClauseSanitiser = $sqlClauseSanitiser;
        $this->objectScopeAccessService = $objectScopeAccessService;
        $this->accountService = $accountService;
        $this->securityService = $securityService;
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
     * Filter dataset instances shared with my account
     *
     * @http GET /shared
     *
     * @param string $filterString
     * @param int $offset
     * @param int $limit
     * @return DatasetInstanceSearchResult[]
     */
    public function filterDatasetInstancesSharedWithAccount($filterString = "", $offset = 0, $limit = 10) {
        return $this->datasetService->filterDatasetInstancesSharedWithAccount($filterString, $offset, $limit);
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
     * Set shared access for a dataset instance for the logged in account
     *
     * @http POST /shareWithCurrentAccount/$datasetInstanceId/$shared
     *
     * @param string $datasetInstanceId
     * @param boolean $shared
     *
     * @return boolean
     */
    public function setSharedAccessForDatasetInstanceForLoggedInAccount($datasetInstanceId, $shared) {
        list ($loggedInUser, $loggedInAccount) = $this->securityService->getLoggedInSecurableAndAccount();
        $scopeAccessGroup = new ScopeAccessGroup([
            new ScopeAccessItem(Role::SCOPE_ACCOUNT, $loggedInAccount->getAccountId())]);
        if ($shared) {
            $this->objectScopeAccessService->assignScopeAccessGroupsToObject(DatasetInstance::class, $datasetInstanceId, [$scopeAccessGroup]);
        } else {
            $this->objectScopeAccessService->removeScopeAccessGroupsFromObject(DatasetInstance::class, $datasetInstanceId, [$scopeAccessGroup->getGroupName()]);
        }
    }


    /**
     * Get shared access groups for dataset instance
     *
     * @http GET /sharedAccessGroups/$datasetInstanceId
     *
     * @param $datasetInstanceId
     * @return ScopeAccessGroup[]
     */
    public function getSharedAccessGroupsForDatasetInstance($datasetInstanceId) {
        return $this->objectScopeAccessService->getScopeAccessGroupsForObject(DatasetInstance::class, $datasetInstanceId);
    }


    /**
     * Revoke access to a group for a shared dataset instance
     *
     * @http DELETE /sharedAccessGroups/$datasetInstanceId
     *
     * @param string $datasetInstanceId
     * @param string $accessGroup
     * @return void
     */
    public function revokeAccessToGroupForDatasetInstance($datasetInstanceId, $accessGroup) {
        $this->objectScopeAccessService->removeScopeAccessGroupsFromObject(DatasetInstance::class, $datasetInstanceId, [$accessGroup]);
    }


    /**
     * Get the invited access groups for a dataset instance
     *
     * @http GET /invitedAccessGroups/$datasetInstanceId
     *
     * @param $datasetInstanceId
     * @return ScopeAccessGroup[]
     */
    public function getInvitedAccessGroupsForDatasetInstance($datasetInstanceId) {
        return $this->objectScopeAccessService->listInvitationsForSharedObject(DatasetInstance::class, $datasetInstanceId);
    }


    /**
     * Invite an account to share a dataset instance
     *
     * @http GET /invitedAccessGroups/$datasetInstanceId/$accountExternalIdentifier
     *
     * @param int $datasetInstanceId
     * @param string $accountExternalIdentifier
     * @param string $expiryDate
     *
     * @objectInterceptorDisabled
     *
     */
    public function inviteAccountToShareDatasetInstance($datasetInstanceId, $accountExternalIdentifier, $expiryDate = null) {

        // Grab account
        $account = $this->accountService->getAccountByExternalIdentifier($accountExternalIdentifier);

        // Invite account using scope access service
        $this->objectScopeAccessService->inviteAccountAccessGroupsToShareObject(DatasetInstance::class, $datasetInstanceId, [
            new ScopeAccessGroup([new ScopeAccessItem(Role::SCOPE_ACCOUNT, $account->getAccountId())], false, false, $expiryDate ? new \DateTime($expiryDate) : null)
        ], "security/dataset-instance-share");
    }


    /**
     * Cancel an invitation for access group
     *
     * @http DELETE /invitedAccessGroups/$datasetInstanceId
     *
     * @param $datasetInstanceId
     * @param $accessGroup
     *
     * @return
     */
    public function cancelInvitationForAccessGroupForDatasetInstance($datasetInstanceId, $accessGroup) {
        $this->objectScopeAccessService->cancelAccountInvitationsForAccessGroups(DatasetInstance::class, $datasetInstanceId, [
            $accessGroup
        ]);
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
