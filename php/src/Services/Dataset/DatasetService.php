<?php

namespace Kinintel\Services\Dataset;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskInterceptor;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\MetaData\MetaDataService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\MVC\Response\Download;
use Kinikit\MVC\Response\Headers;
use Kinikit\MVC\Response\Response;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfile;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSummary;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\Exporter\DatasetExporter;
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
    public function getDataSetInstance($id, $enforceReadOnly = true) {
        return DatasetInstance::fetch($id)->returnSummary($enforceReadOnly);
    }


    /**
     * Get an extended version of a dataset instance
     *
     * @param $originalDatasetId
     * @return DatasetInstanceSummary
     */
    public function getExtendedDatasetInstance($originalDatasetId) {
        $originalDataset = $this->getDataSetInstance($originalDatasetId, false);
        return new DatasetInstanceSummary($originalDataset->getTitle() . " Extended", null, $originalDatasetId, [], [], [], null, null, []);
    }


    /**
     * Get a full data set instance
     *
     * @param $id
     * @return mixed
     */
    public function getFullDataSetInstance($id) {
        return DatasetInstance::fetch($id);
    }


    /**
     * Get all full data set instances
     *
     */
    public function getAllFullDataSetInstances() {
        return DatasetInstance::filter("");
    }


    /**
     * Get dataset instance by title optionally limited to account and project.
     *
     * @param $title
     * @param null $projectKey
     * @param string $accountId
     */
    public function getDataSetInstanceByTitle($title, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // If account id or project key, form clause
        $clauses = ["title = ?"];
        $parameters = [$title];
        if ($accountId || $projectKey) {
            $clauses[] = "accountId = ?";
            $parameters[] = $accountId;

            if ($projectKey) {
                $clauses[] = "projectKey = ?";
                $parameters[] = $projectKey;
            }
        } else {
            $clauses[] = "accountId IS NULL";
        }


        $matches = DatasetInstance::filter("WHERE " . implode(" AND ", $clauses), $parameters);
        if (sizeof($matches) > 0) {
            return $matches[0]->returnSummary();
        } else {
            throw new ObjectNotFoundException(DatasetInstance::class, $title);
        }

    }


    /**
     * Filter data set instances optionally limiting by the passed filter string,
     * array of tags and project id.
     *
     * @param string $filterString
     * @param array $categories
     * @param array $tags
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     */
    public function filterDataSetInstances($filterString = "", $categories = [], $tags = [], $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

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

            if ($tags[0] == "NONE") {
                $query .= " AND tags.tag_key IS NULL";
            } else {
                $query .= " AND tags.tag_key IN (" . str_repeat("?", sizeof($tags)) . ")";
                $params = array_merge($params, $tags);
            }
        }

        if ($categories && sizeof($categories) > 0) {
            $query .= " AND categories.category_key IN (?" . str_repeat(",?", sizeof($categories) - 1) . ")";
            $params = array_merge($params, $categories);
        }

        $query .= " ORDER BY title LIMIT $limit OFFSET $offset";

        // Return a summary array
        return array_map(function ($instance) {
            $summary = $instance->returnSummary();
            return new DatasetInstanceSearchResult($instance->getId(), $summary->getTitle(), $summary->getSummary(), $summary->getDescription(),
                $summary->getCategories());
        },
            DatasetInstance::filter($query, $params));

    }


    /**
     * Get In Use Dataset Instance categories
     *
     * @param string[] $tags
     * @param string $projectKey
     * @param integer $accountId
     */
    public function getInUseDatasetInstanceCategories($tags = [], $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $params = [];
        if (!$accountId) {
            $query = "WHERE accountId IS NULL";
        } else {
            $query = "WHERE accountId = ?";
            $params[] = $accountId;
        }

        if ($projectKey) {
            $query .= " AND project_key = ?";
            $params[] = $projectKey;
        }

        if ($tags && sizeof($tags) > 0) {
            $query .= " AND tags.tag_key IN (" . str_repeat("?", sizeof($tags)) . ")";
            $params = array_merge($params, $tags);
        }

        $categoryKeys = DatasetInstance::values("DISTINCT(categories.category_key)", $query, $params);

        return $this->metaDataService->getMultipleCategoriesByKey($categoryKeys, $projectKey, $accountId);

    }

    /**
     * Save a data set instance
     *
     * @param DatasetInstanceSummary $dataSetInstanceSummary
     */
    public function saveDataSetInstance($dataSetInstanceSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $dataSetInstance = new DatasetInstance($dataSetInstanceSummary, $accountId, $projectKey);

        // If existing summary, ensure we sync snapshot profiles.
        if ($dataSetInstanceSummary->getId()) {
            $existingDataSetInstance = DatasetInstance::fetch($dataSetInstanceSummary->getId());
            $dataSetInstance->setSnapshotProfiles($existingDataSetInstance->getSnapshotProfiles());
        }

        // Process tags
        if (sizeof($dataSetInstanceSummary->getTags())) {
            $tags = $this->metaDataService->getObjectTagsFromSummaries($dataSetInstanceSummary->getTags(), $accountId, $projectKey);
            $dataSetInstance->setTags($tags);
        }

        // Process categories
        if (sizeof($dataSetInstanceSummary->getCategories())) {
            $categories = $this->metaDataService->getObjectCategoriesFromSummaries($dataSetInstanceSummary->getCategories(), $accountId, $projectKey);
            $dataSetInstance->setCategories($categories);
        }


        $dataSetInstance->save();
        return $dataSetInstance->getId();
    }


    /**
     * Update meta data for a dataset instance
     *
     * @param DatasetInstanceSearchResult $datasetInstanceSearchResult
     */
    public function updateDataSetMetaData($datasetInstanceSearchResult) {

        $dataset = DatasetInstance::fetch($datasetInstanceSearchResult->getId());
        $dataset->setTitle($datasetInstanceSearchResult->getTitle());
        $dataset->setSummary($datasetInstanceSearchResult->getSummary());
        $dataset->setDescription($datasetInstanceSearchResult->getDescription());
        $dataset->setCategories($this->metaDataService->getObjectCategoriesFromSummaries($datasetInstanceSearchResult->getCategories(), $dataset->getAccountId(), $dataset->getProjectKey()));
        $dataset->save();

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
     * Filter snapshot profiles for accounts, optionally by project key and tags.
     *
     * @param string $filterString
     * @param array $tags
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param string $accountId
     */
    public function filterSnapshotProfiles($filterString = "", $tags = [], $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {


        $clauses = [];
        $params = [];
        if ($accountId) {
            $clauses[] = "datasetInstanceLabel.account_id = ?";
            $params[] = $accountId;
        }
        if ($projectKey) {
            $clauses[] = "datasetInstanceLabel.project_key = ?";
            $params[] = $projectKey;
        }

        if ($tags && sizeof($tags) > 0) {
            if ($tags[0] == "NONE") {
                $clauses[] = "datasetInstanceLabel.tags.tag_key IS NULL";
            } else {
                $clauses[] = "datasetInstanceLabel.tags.tag_key IN (" . str_repeat("?", sizeof($tags)) . ")";
                $params = array_merge($params, $tags);
            }
        }

        if ($filterString) {
            $clauses[] = "(title LIKE ? OR datasetInstanceLabel.title LIKE ?)";
            $params[] = "%$filterString%";
            $params[] = "%$filterString%";
        }

        $query = sizeof($clauses) ? "WHERE " . join(" AND ", $clauses) : "";
        $query .= " ORDER BY datasetInstanceLabel.title, title";

        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
        }
        if ($offset) {
            $query .= " OFFSET ?";
            $params[] = $offset;
        }


        $snapshotProfiles = DatasetInstanceSnapshotProfile::filter($query, $params);


        return array_map(function ($snapshotProfile) {
            return new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile);
        }, $snapshotProfiles);


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

        // Update the processor configuration object to include the datasetInstanceId
        $processorConfig = $snapshotProfileSummary->getProcessorConfig() ?? [];
        $processorConfig["datasetInstanceId"] = $datasetInstanceId;


        // If an existing profile we want to update the master one
        if ($snapshotProfileSummary->getId()) {

            $snapshotProfile = DatasetInstanceSnapshotProfile::fetch($snapshotProfileSummary->getId());
            if ($snapshotProfile->getDatasetInstanceId() != $datasetInstanceId)
                throw new ObjectNotFoundException(DatasetInstanceSnapshotProfile::class, $snapshotProfileSummary->getId());

            if ($snapshotProfileSummary->getTrigger() == DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE) {

                if (!$snapshotProfile->getScheduledTask()) {
                    $dataProcessorKey = $processorConfig["snapshotIdentifier"];
                    $snapshotProfile->setScheduledTask(new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "Dataset Instance Snapshot:$datasetInstanceId - " . $snapshotProfileSummary->getTitle(),
                        [
                            "dataProcessorKey" => $dataProcessorKey
                        ], $snapshotProfileSummary->getTaskTimePeriods()), $datasetInstance->getProjectKey(), $datasetInstance->getAccountId()));
                }


                $snapshotProfile->setTrigger(DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE);

                $snapshotProfile->getScheduledTask()->setTimePeriods($snapshotProfileSummary->getTaskTimePeriods());
            } else {

                if (!$snapshotProfile->getScheduledTask()) {
                    $dataProcessorKey = $processorConfig["snapshotIdentifier"];
                    $snapshotProfile->setScheduledTask(new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "Dataset Instance Snapshot:$datasetInstanceId - " . $snapshotProfileSummary->getTitle(),
                        [
                            "dataProcessorKey" => $dataProcessorKey
                        ], []), $datasetInstance->getProjectKey(), $datasetInstance->getAccountId()));
                }

                $snapshotProfile->setTrigger(DatasetInstanceSnapshotProfileSummary::TRIGGER_ADHOC);
                $snapshotProfile->getScheduledTask()->setTimePeriods([]);
            }


            $snapshotProfile->setTitle($snapshotProfileSummary->getTitle());

            $processorConfig["snapshotIdentifier"] = $snapshotProfile->getDataProcessorInstance()->getKey();
            $snapshotProfile->getDataProcessorInstance()->setConfig($processorConfig);


        } // Otherwise create new
        else {

            $dataProcessorKey = "dataset_snapshot_" . (new \DateTime())->format("Uv");

            $processorConfig["snapshotIdentifier"] = $dataProcessorKey;


            // Create a processor instance
            $dataProcessorInstance = new DataProcessorInstance($dataProcessorKey,
                $snapshotProfileSummary->getTitle(),
                $snapshotProfileSummary->getProcessorType(), $processorConfig,
                $datasetInstance->getProjectKey(), $datasetInstance->getAccountId());


            $scheduledTask = $snapshotProfileSummary->getTrigger() == DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE ? new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "Dataset Instance Snapshot:$datasetInstanceId - " . $snapshotProfileSummary->getTitle(),
                [
                    "dataProcessorKey" => $dataProcessorKey
                ], $snapshotProfileSummary->getTaskTimePeriods()), $datasetInstance->getProjectKey(), $datasetInstance->getAccountId())
                : new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "Dataset Instance Snapshot:$datasetInstanceId - " . $snapshotProfileSummary->getTitle(),
                    [
                        "dataProcessorKey" => $dataProcessorKey
                    ], []), $datasetInstance->getProjectKey(), $datasetInstance->getAccountId());


            $snapshotProfile = new DatasetInstanceSnapshotProfile($datasetInstanceId, $snapshotProfileSummary->getTitle(), $snapshotProfileSummary->getTrigger(), $scheduledTask, $dataProcessorInstance);


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

        // Security check to ensure we can access the parent instance
        DatasetInstance::fetch($datasetInstanceId);

        // Grab the profile
        $profile = DatasetInstanceSnapshotProfile::fetch($snapshotProfileId);

        if ($profile->getDatasetInstanceId() == $datasetInstanceId) {
            $profile->remove();
        }


    }


    /**
     * Trigger a snapshot manually
     *
     * @param $datasetInstanceId
     * @param $snapshotProfileId
     *
     */
    public function triggerSnapshot($datasetInstanceId, $snapshotProfileId) {

        // Security check to ensure we can access the parent instance
        DatasetInstance::fetch($datasetInstanceId);

        // Grab the profile
        /**
         * @var DatasetInstanceSnapshotProfile $profile
         */
        $profile = DatasetInstanceSnapshotProfile::fetch($snapshotProfileId);

        $task = $profile->getScheduledTask();
        $task->setNextStartTime(new \DateTime());
        $task->setStatus(ScheduledTask::STATUS_PENDING);

        // Suppress normal schedule behaviour here
        $preDisabled = ScheduledTaskInterceptor::$disabled;
        ScheduledTaskInterceptor::$disabled = true;
        $task->save();
        ScheduledTaskInterceptor::$disabled = $preDisabled;

    }


    /**
     * Get evaluated parameters for the passed datasource by id - this includes parameters from both
     * the dataset and datasource concatenated.
     *
     * @param DatasetInstanceSummary $datasourceInstanceSummary
     *
     * @return Parameter[]
     */
    public function getEvaluatedParameters($datasetInstanceSummary) {

        $params = [];
        if ($datasetInstanceSummary->getDatasourceInstanceKey()) {
            $params = $this->datasourceService->getEvaluatedParameters($datasetInstanceSummary->getDatasourceInstanceKey());
        } else if ($datasetInstanceSummary->getDatasetInstanceId()) {
            $parentDatasetInstanceSummary = $this->getDataSetInstance($datasetInstanceSummary->getDatasetInstanceId(), false);
            $params = $this->getEvaluatedParameters($parentDatasetInstanceSummary);
        }

        $params = array_merge($params, $datasetInstanceSummary->getParameters() ?? []);
        return $params;
    }


    /**
     * Wrapper to below function for standard read only use where a data set is being
     * queried
     *
     * @param $dataSetInstanceId
     * @param TransformationInstance[] $additionalTransformations
     */
    public function getEvaluatedDataSetForDataSetInstanceById($dataSetInstanceId, $parameterValues = [], $additionalTransformations = [], $offset = null, $limit = null) {

        $dataSetInstance = $this->getDataSetInstance($dataSetInstanceId, false);

        return $this->getEvaluatedDataSetForDataSetInstance($dataSetInstance, $parameterValues, $additionalTransformations, $offset, $limit);
    }


    /**
     * Wrapper to below function which also calls the materialise function to just return
     * the dataset.  This is the normal function called to produce charts / tables etc for end
     * use.
     *
     * @param DatasetInstanceSummary $dataSetInstance
     * @param TransformationInstance[] $additionalTransformations
     *
     * @return Dataset
     *
     */
    public function getEvaluatedDataSetForDataSetInstance($dataSetInstance, $parameterValues = [], $additionalTransformations = [], $offset = null, $limit = null) {


        // Aggregate transformations and parameter values.
        $transformations = array_merge($dataSetInstance->getTransformationInstances() ?? [], $additionalTransformations ?? []);
        $parameterValues = array_merge($dataSetInstance->getParameterValues() ?? [], $parameterValues ?? []);

        // Call the appropriate function depending whether a datasource / dataset was being targeted.
        if ($dataSetInstance->getDatasourceInstanceKey()) {
            return $this->datasourceService->getEvaluatedDataSource($dataSetInstance->getDatasourceInstanceKey(), $parameterValues,
                $transformations, $offset, $limit);
        } else if ($dataSetInstance->getDatasetInstanceId()) {
            return $this->getEvaluatedDataSetForDataSetInstanceById($dataSetInstance->getDatasetInstanceId(), $parameterValues, $transformations, $offset, $limit);
        }
    }


    /**
     * Get transformed datasource for a data set instance, calling recursively as required for datasets
     *
     * @param DatasetInstanceSummary $dataSetInstance
     * @param mixed[] $parameterValues
     * @param TransformationInstance[] $additionalTransformations
     */
    public function getTransformedDatasourceForDataSetInstance($dataSetInstance, $parameterValues = [], $additionalTransformations = []) {


        // Aggregate transformations and parameter values.
        $transformations = array_merge($dataSetInstance->getTransformationInstances() ?? [], $additionalTransformations ?? []);
        $parameterValues = array_merge($dataSetInstance->getParameterValues() ?? [], $parameterValues ?? []);

        if ($dataSetInstance->getDatasourceInstanceKey()) {
            list ($dataSource, $parameterValues) = $this->datasourceService->getTransformedDataSource($dataSetInstance->getDatasourceInstanceKey(), $transformations, $parameterValues);
            return $dataSource;
        } else if ($dataSetInstance->getDatasetInstanceId()) {
            $dataset = $this->getDataSetInstance($dataSetInstance->getDatasetInstanceId(), false);
            return $this->getTransformedDatasourceForDataSetInstance($dataset, $parameterValues, $transformations);
        }

    }

    /**
     * Export a dataset using a defined exporter and configuration
     *
     * @param DatasetInstanceSummary $datasetInstance
     * @param string $exporterKey
     * @param mixed $exporterConfiguration
     * @param array $parameterValues
     * @param TransformationInstance[] $additionalTransformations
     * @param int $offset
     * @param int $limit
     *
     * @return Response
     *
     */
    public function exportDatasetInstance($datasetInstance, $exporterKey, $exporterConfiguration = null, $parameterValues = [], $additionalTransformations = [], $offset = 0, $limit = 25, $streamAsDownload = true, $cacheTime = 0) {

        /**
         * Get an exporter instance
         *
         * @var DatasetExporter $exporter
         */
        $exporter = Container::instance()->getInterfaceImplementation(DatasetExporter::class, $exporterKey);

        // Validate configuration
        $exporterConfiguration = $exporter->validateConfig($exporterConfiguration);


         // Grab the dataset.
        $dataset = $this->getEvaluatedDataSetForDataSetInstance($datasetInstance, $parameterValues, $additionalTransformations, $offset, $limit);





        // Export the dataset using exporter
        $contentSource = $exporter->exportDataset($dataset, $exporterConfiguration);

        // Add headers to the party
        $headers = [
            Headers::HEADER_CACHE_CONTROL => "public, max-age=" . $cacheTime
        ];

        // Return a new download or regular response depending upon then
        if ($streamAsDownload) {
            $filename = str_replace(" ", "_", strtolower($datasetInstance->getTitle())) . "-" . date("U") . "." . $exporter->getDownloadFileExtension($exporterConfiguration);
            return new Download($contentSource, $filename, 200, $headers);
        } else {
            return new SimpleResponse($contentSource, 200, $headers);
        }
    }


}
