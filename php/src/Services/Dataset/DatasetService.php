<?php

namespace Kinintel\Services\Dataset;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\MetaData\MetaDataService;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Exception\UnsupportedDatasourceTransformationException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfile;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSummary;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class DatasetService {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var MetaDataService
     */
    private $metaDataService;


    /**
     * DatasetService constructor.
     *
     * @param DatasourceService $datasourceService
     * @param MetaDataService $metaDataService
     */
    public function __construct($datasourceService, $metaDataService) {
        $this->datasourceService = $datasourceService;
        $this->metaDataService = $metaDataService;
    }


    /**
     * Get a data set instance by id
     *
     * @param $id
     * @return DatasetInstanceSummary
     */
    public function getDataSetInstance($id) {
        return DatasetInstance::fetch($id)->returnSummary();
    }


    /**
     * Filter data set instances optionally limiting by the passed filter string,
     * array of tags and project id.
     *
     * @param string $filterString
     * @param array $tags
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     */
    public function filterDataSetInstances($filterString = "", $tags = [], $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $params = [];
        if ($accountId === null) {
            $query = "WHERE accountId IS NULL";
        } else {
            $query = "WHERE accountId = ?";
            $params[] = $accountId;
        }

        if ($filterString) {
            $query .= " AND title LIKE ?";
            $params[] = "%$filterString%";
        }

        if ($projectKey) {
            $query .= " AND project_key = ?";
            $params[] = $projectKey;
        }

        if ($tags && sizeof($tags) > 0) {
            $query .= " AND tags.tag_key IN (" . str_repeat("?", sizeof($tags)) . ")";
            $params = array_merge($params, $tags);
        }


        $query .= " ORDER BY title LIMIT $limit OFFSET $offset";

        // Return a summary array
        return array_map(function ($instance) {
            return new DatasetInstanceSearchResult($instance->getId(), $instance->getTitle());
        },
            DatasetInstance::filter($query, $params));

    }


    /**
     * Save a data set instance
     *
     * @param DatasetInstanceSummary $dataSetInstanceSummary
     */
    public function saveDataSetInstance($dataSetInstanceSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $dataSetInstance = new DatasetInstance($dataSetInstanceSummary, $accountId, $projectKey);

        // Process tags
        if (sizeof($dataSetInstanceSummary->getTags())) {
            $tags = $this->metaDataService->getObjectTagsFromSummaries($dataSetInstanceSummary->getTags(), $accountId, $projectKey);
            $dataSetInstance->setTags($tags);
        }


        $dataSetInstance->save();
        return $dataSetInstance->getId();
    }


    /**
     * Remove the data set instance by id
     *
     * @param $id
     */
    public function removeDataSetInstance($id) {
        $dataSetInstance = DatasetInstance::fetch($id);
        $dataSetInstance->remove();
    }


    /**
     * List all snapshots for a dataset instance
     *
     * @param $datasetInstanceId
     * @return DatasetInstanceSnapshotProfileSummary[]
     */
    public function listSnapshotProfilesForDataSetInstance($datasetInstanceId) {

        // Check we have access to the instance first
        DatasetInstance::fetch($datasetInstanceId);

        $profiles = DatasetInstanceSnapshotProfile::filter("WHERE datasetInstanceId = ? ORDER BY title", $datasetInstanceId);
        return array_map(function ($profile) {
            return $profile->returnSummary();
        }, $profiles);
    }


    /**
     * Save a snapshot profile for an instance
     *
     * @param DatasetInstanceSnapshotProfileSummary $snapshotProfileSummary
     * @param integer $datasetInstanceId
     */
    public function saveSnapshotProfile($snapshotProfileSummary, $datasetInstanceId) {

        // Security check to ensure we can access the parent instance
        $datasetInstance = DatasetInstance::fetch($datasetInstanceId);

        // If an existing profile we want to update the master one
        if ($snapshotProfileSummary->getId()) {

            $snapshotProfile = DatasetInstanceSnapshotProfile::fetch($snapshotProfileSummary->getId());
            $snapshotProfile->setTitle($snapshotProfileSummary->getTitle());
            $snapshotProfile->getScheduledTask()->setTimePeriods($snapshotProfileSummary->getTaskTimePeriods());
            $snapshotProfile->getDataProcessorInstance()->setConfig($snapshotProfileSummary->getProcessorConfig());


        } // Otherwise create new
        else {

            $dataProcessorKey = "dataset-snapshot-" . date("U");

            // Create a processor instance
            $dataProcessorInstance = new DataProcessorInstance($dataProcessorKey,
                "Dataset Instance Snapshot: " . $datasetInstance->getId() . " - " . $snapshotProfileSummary->getTitle(),
                $snapshotProfileSummary->getProcessorType(), $snapshotProfileSummary->getProcessorConfig(),
                $datasetInstance->getProjectKey(), $datasetInstance->getAccountId());


            $snapshotProfile = new DatasetInstanceSnapshotProfile($datasetInstanceId, $snapshotProfileSummary->getTitle(),
                new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "Dataset Instance Snapshot:$datasetInstanceId - " . $snapshotProfileSummary->getTitle(),
                    [
                        "dataProcessorKey" => $dataProcessorKey
                    ], $snapshotProfileSummary->getTaskTimePeriods()), $datasetInstance->getProjectKey(), $datasetInstance->getAccountId()),
                $dataProcessorInstance);


        }


        // Save the profile
        $snapshotProfile->save();


        return $snapshotProfile->getId();


    }


    /**
     * Remove a snapshot profile for an instance
     *
     * @param $datasetInstanceId
     * @param $snapshotProfileId
     */
    public function removeSnapshotProfile($datasetInstanceId, $snapshotProfileId) {

    }


    /**
     * Get evaluated parameters for the passed datasource by id - this includes parameters from both
     * the dataset and datasource concatenated.
     *
     * @param string $datasourceInstanceKey
     *
     * @return Parameter[]
     */
    public function getEvaluatedParameters($datasetInstanceId) {
        $dataset = $this->getDataSetInstance($datasetInstanceId);
        $params = $this->datasourceService->getEvaluatedParameters($dataset->getDatasourceInstanceKey());
        $params = array_merge($params, $dataset->getParameters() ?? []);
        return $params;
    }


    /**
     * Wrapper to below function for standard read only use where a data set is being
     * queried
     *
     * @param $dataSetInstanceId
     * @param TransformationInstance[] $additionalTransformations
     */
    public function getEvaluatedDataSetForDataSetInstanceById($dataSetInstanceId, $additionalTransformations = []) {
        $dataSetInstance = $this->getDataSetInstance($dataSetInstanceId);
        return $this->getEvaluatedDataSetForDataSetInstance($dataSetInstance, $additionalTransformations);
    }


    /**
     * Wrapper to below function which also calls the materialise function to just return
     * the dataset.  This is the normal function called to produce charts / tables etc for end
     * use.
     *
     * @param DatasetInstanceSummary $dataSetInstance
     * @param TransformationInstance[] $additionalTransformations
     *
     */
    public function getEvaluatedDataSetForDataSetInstance($dataSetInstance, $additionalTransformations = []) {

        $transformations = array_merge($dataSetInstance->getTransformationInstances() ?? [], $additionalTransformations ?? []);

        return $this->datasourceService->getEvaluatedDataSource($dataSetInstance->getDatasourceInstanceKey(), [],
            $transformations);
    }


}