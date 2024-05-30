<?php

namespace Kinintel\Services\Dataset;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\MVC\Response\Download;
use Kinikit\MVC\Response\Headers;
use Kinikit\MVC\Response\Response;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\Exporter\DatasetExporter;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * @interceptor \Kinintel\Services\Dataset\DatasetServiceInterceptor
 */
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
     * @var ActiveRecordInterceptor
     */
    private $activeRecordInterceptor;


    /**
     * DatasetService constructor.
     *
     * @param DatasourceService $datasourceService
     * @param MetaDataService $metaDataService
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     */
    public function __construct($datasourceService, $metaDataService, $activeRecordInterceptor) {
        $this->datasourceService = $datasourceService;
        $this->metaDataService = $metaDataService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
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
     * Get dataset instance by management key
     *
     * @param string $managementKey
     * @return DatasetInstanceSummary
     */
    public function getDatasetInstanceByManagementKey($managementKey, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->getFullDataSetInstanceByManagementKey($managementKey, $accountId)->returnSummary();
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
     * Get full dataset instance
     *
     * @param string $managementKey
     * @param integer $accountId
     *
     * @return DatasetInstance
     */
    public function getFullDataSetInstanceByManagementKey($managementKey, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $sql = "WHERE managementKey = ?";
        $params = [$managementKey];

        if ($accountId) {
            $sql .= " AND accountId = ?";
            $params[] = $accountId;
        } else {
            $sql .= " AND account_id IS NULL";
        }

        $matches = DatasetInstance::filter($sql, $params);
        if (sizeof($matches) > 0) {
            return $matches[0];
        } else {
            throw new ObjectNotFoundException(DatasetInstance::class, $managementKey);
        }
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
            return new DatasetInstanceSearchResult($instance->getId(), $summary->getTitle(), $summary->getSummary(),
                $summary->getDescription(), $summary->getCategories());
        },
            DatasetInstance::filter($query, $params));

    }


    /**
     * Filter dataset instances shared with account.
     *
     * @param string $filterString
     * @param integer $offset
     * @param integer $limit
     * @param integer $accountId
     * @return DatasetInstanceSearchResult[]
     */
    public function filterDatasetInstancesSharedWithAccount($filterString = "", $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $matches = DatasetInstance::filter("WHERE objectScopeAccesses.recipient_scope = ? AND objectScopeAccesses.recipient_primary_key = ? AND title LIKE ? LIMIT ? OFFSET ?",
            Role::SCOPE_ACCOUNT, $accountId, "%$filterString%", $limit, $offset);


        return array_map(function ($datasetInstance) {
            return new DatasetInstanceSearchResult($datasetInstance->getId(),
                $datasetInstance->getTitle(),
                $datasetInstance->getSummary(),
                $datasetInstance->getDescription(), [], null, null,
                $datasetInstance->getAccountSummary()?->getName(),
                $datasetInstance->getAccountSummary()?->getLogo());
        }, $matches);


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
     * Check whether an import key is available for a supplied datasource instance.
     *
     * @param DatasetInstance $datasetInstance
     * @return boolean
     */
    public function managementKeyAvailableForDatasetInstance($datasetInstance, $proposedManagementKey) {

        // If account id or project key, form clause
        $clauses = ["management_key = ?"];
        $parameters = [$proposedManagementKey];
        if ($datasetInstance->getAccountId() || $datasetInstance->getProjectKey()) {
            $clauses[] = "accountId = ?";
            $parameters[] = $datasetInstance->getAccountId();
        } else {
            $clauses[] = "accountId IS NULL";
        }
        if ($datasetInstance->getId()) {
            $clauses[] = "id <> ?";
            $parameters[] = $datasetInstance->getId();
        }

        $matches = DatasetInstance::filter("WHERE " . implode(" AND ", $clauses), $parameters);
        return sizeof($matches) ? false : true;
    }


    /**
     * Get evaluated parameters for the passed datasource by id - this includes parameters from both
     * the dataset and datasource concatenated.
     *
     * @param DatasetInstanceSummary $datasourceInstanceSummary
     *
     * @return Parameter[]
     */
    public function getEvaluatedParameters($dataSetInstance) {

        $params = [];
        if ($dataSetInstance->getDatasourceInstanceKey()) {
            $params = $this->datasourceService->getEvaluatedParameters($dataSetInstance->getDatasourceInstanceKey());
        } else if ($dataSetInstance->getDatasetInstanceId()) {
            $parentDatasetInstanceSummary = $this->getDataSetInstance($dataSetInstance->getDatasetInstanceId(), false);
            $params = $this->getEvaluatedParameters($parentDatasetInstanceSummary);
        }

        $params = array_merge($params, $dataSetInstance->getParameters() ?? []);
        return $params;
    }


    /**
     * Wrapper to below function for standard read only use where a data set is being
     * queried
     *
     * @param $dataSetInstanceId
     * @param TransformationInstance[] $additionalTransformations
     *
     * @return Dataset
     */
    public function getEvaluatedDataSetForDataSetInstanceById($dataSetInstanceId, $parameterValues = [], $additionalTransformations = [], $offset = null, $limit = null) {

        $dataSetInstance = $this->getFullDatasetInstance($dataSetInstanceId);

        return $this->getEvaluatedDataSetForDataSetInstance($dataSetInstance, $parameterValues, $additionalTransformations, $offset, $limit);
    }


    /**
     * @param DatasetInstanceSummary $dataSetInstance
     * @param array $parameterValues
     * @param TransformationInstance[] $additionalTransformations
     * @param int $offset
     * @param int $limit
     * @return Dataset
     */
    public function getEvaluatedDataSetForDataSetInstance($dataSetInstance, $parameterValues = [], $additionalTransformations = [], $offset = null, $limit = null) {


        // Aggregate transformations and parameter values.
        $transformations = array_merge($dataSetInstance->getTransformationInstances() ?? [], $additionalTransformations ?? []);
        $parameterValues = array_merge($dataSetInstance->getParameterValues() ?? [], $parameterValues ?? []);

        // Call the appropriate function depending whether a datasource / dataset was being targeted.
        if ($dataSetInstance->getDatasourceInstanceKey()) {
            return $this->datasourceService->getEvaluatedDataSourceByInstanceKey($dataSetInstance->getDatasourceInstanceKey(), $parameterValues,
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
            list ($dataSource, $parameterValues) = $this->datasourceService->getTransformedDataSourceByInstanceKey($dataSetInstance->getDatasourceInstanceKey(), $transformations, $parameterValues);
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
