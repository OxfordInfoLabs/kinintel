<?php


namespace Kinintel\Services\Datasource;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\ValueFunction\ValueFunctionEvaluator;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\InvalidParametersException;
use Kinintel\Exception\UnsupportedDatasourceTransformationException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Hook\DatasourceHookService;
use Kinintel\ValueObjects\Application\DataSearchItem;
use Kinintel\ValueObjects\Dataset\DatasetTree;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\IndexableDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateResult;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Paging\PagingMarkerTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * @interceptor \Kinintel\Services\Datasource\DatasourceServiceInterceptor
 */
class DatasourceService {


    public function __construct(
        private DatasourceDAO          $datasourceDAO,
        private SecurityService        $securityService,
        private ValueFunctionEvaluator $valueFunctionEvaluator,
        private DataProcessorService   $dataProcessorService,
        private DatasourceHookService  $datasourceHookService
    ) {
    }


    /**
     * Get an array of filtered datasources using passed filter string to limit on
     * name of data source.  This checks both local datasources and database ones.
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     * @param array $includedTypes
     * @param string $projectKey
     */
    public function filterDatasourceInstances($filterString = "", $limit = 10, $offset = 0, $includedTypes = [], $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->datasourceDAO->filterDatasourceInstances($filterString, $limit, $offset, $includedTypes, $projectKey, $accountId);
    }


    /**
     * Get a datasource instance by key
     *
     * @param $key
     * @return DatasourceInstance
     */
    public function getDataSourceInstanceByKey($key) {
        return $this->datasourceDAO->getDataSourceInstanceByKey($key);
    }


    /**
     * Get a datasource instance by key
     *
     * @param $importKey
     * @return DatasourceInstance
     */
    public function getDataSourceInstanceByImportKey($importKey, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->datasourceDAO->getDatasourceInstanceByImportKey($importKey, $accountId);
    }


    /**
     * Get a datasource instance by title - usually for a specific project / account for comparison matching
     *
     * @param string $title
     * @param string $projectKey
     * @param integer $accountId
     *
     * @return DatasourceInstance
     * @throws ObjectNotFoundException
     */
    public function getDatasourceInstanceByTitle($title, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->datasourceDAO->getDatasourceInstanceByTitle($title, $projectKey, $accountId);
    }

    /**
     * Get a dataset tree for a datasource key.   This will only
     * return a value if the datasource has an account id to ignore built in
     * datasources.
     *
     * @param $datasourceKey
     * @return DatasetTree|null
     */
    public function getDatasetTreeForDatasourceKey($datasourceKey) {
        $datasource = $this->getDataSourceInstanceByKey($datasourceKey);

        // Only record as datasets datasources which are owned by the user.
        if ($datasource->getAccountId()) {
            if ($datasource->getType() == "snapshot") {
                if (str_ends_with($datasourceKey, "_latest"))
                    $datasourceKey = substr($datasourceKey, 0, strlen($datasourceKey) - 7);
                if (str_ends_with($datasourceKey, "_pending"))
                    $datasourceKey = substr($datasourceKey, 0, strlen($datasourceKey) - 8);

                // Grab the matching processor
                $dataProcessor = $this->dataProcessorService->getDataProcessorInstance($datasourceKey);

                $dataItem = new DataSearchItem("snapshot", $dataProcessor->getKey(), $dataProcessor->getTitle(), "",
                    $dataProcessor?->getAccountSummary()?->getName(),
                    $dataProcessor?->getAccountSummary()?->getLogo());

                $datasetService = Container::instance()->get(DatasetService::class);
                return new DatasetTree($dataItem, $datasetService->getDatasetTreeByInstanceId($dataProcessor->getRelatedObjectKey()));

            } else {
                $dataItem = new DataSearchItem($datasource->getType(), $datasource->getKey(), $datasource->getTitle(), "",
                    $datasource?->getAccountSummary()?->getName(),
                    $datasource?->getAccountSummary()?->getLogo());
                return new DatasetTree($dataItem);
            }
        }

        return null;

    }


    /**
     * Save a datasource instance
     *
     * @param DatasourceInstance $dataSourceInstance
     * @return DatasourceInstance
     */
    public function saveDataSourceInstance($dataSourceInstance) {
        $this->datasourceDAO->saveDataSourceInstance($dataSourceInstance);
        return $dataSourceInstance;
    }


    /**
     * Remove a datasource instance by key
     *
     * @param $dataSourceInstanceKey
     */
    public function removeDatasourceInstance($dataSourceInstanceKey) {
        // Remove the instance
        $this->datasourceDAO->removeDatasourceInstance($dataSourceInstanceKey);
    }


    /**
     * Get evaluated parameters for the passed datasource and transformations array
     *
     * @param string $datasourceInstanceKey
     *
     * @return Parameter[]
     * @throws ObjectNotFoundException
     */
    public function getEvaluatedParameters($datasourceInstanceKey) {

        // Grab the datasource for this data set instance by key
        $datasourceInstance = $this->datasourceDAO->getDataSourceInstanceByKey($datasourceInstanceKey);

        // Grab parameters from top level data source instance
        $parameters = $datasourceInstance->getParameters();

        return $parameters;

    }


    /**
     * Get the evaluated data source for a source specified by key, using the supplied parameter values and applying the
     * passed transformations
     *
     * @param string $datasourceInstanceKey
     * @param mixed[] $parameterValues
     * @param TransformationInstance[] $transformations
     *
     * @return Dataset
     */
    public function getEvaluatedDataSourceByInstanceKey($datasourceInstanceKey, $parameterValues = [], $transformations = [], $offset = null, $limit = null) {

        /** @var Datasource $datasource */
        list($datasource, $parameterValues) = $this->getTransformedDataSourceByInstanceKey($datasourceInstanceKey, $transformations, $parameterValues, $offset, $limit);

        // Return the evaluated data source
        return $datasource->materialise($parameterValues ?? []);

    }


    /**
     * Get the evaluated data source for a source specified by key, using the supplied parameter values and applying the
     * passed transformations
     *
     * @param DatasourceInstance $datasourceInstance
     * @param mixed[] $parameterValues
     * @param TransformationInstance[] $additionalTransformations
     *
     * @return Dataset
     */
    public function getEvaluatedDataSource($datasourceInstance, $parameterValues = [], $transformations = [], $offset = null, $limit = null) {

        list($datasource, $parameterValues) = $this->getTransformedDataSource($datasourceInstance, $transformations, $parameterValues, $offset, $limit);

        // Return the evaluated data source
        return $datasource->materialise($parameterValues ?? []);

    }


    /**
     * Get a transformed data source
     *
     * @param $datasourceInstanceKey
     * @param $transformations
     * @param mixed $parameterValues
     *
     * @return [Datasource, array]
     * @throws InvalidParametersException
     * @throws ObjectNotFoundException
     * @throws UnsupportedDatasourceTransformationException
     * @throws ValidationException
     */
    public function getTransformedDataSourceByInstanceKey($datasourceInstanceKey, $transformations, $parameterValues, $offset = null, $limit = null) {

        // Grab the datasource for this data set instance by key
        $datasourceInstance = $this->datasourceDAO->getDataSourceInstanceByKey($datasourceInstanceKey);

        // Return the transformed data source
        return $this->getTransformedDataSource($datasourceInstance, $transformations, $parameterValues, $offset, $limit);
    }


    /**
     * Get a transformed datasource instance
     *
     * @param DatasourceInstance $datasourceInstance
     * @param TransformationInstance[] $transformations
     * @param mixed[] $parameterValues
     * @param integer $offset
     * @param integer $limit
     * @return []
     */
    public function getTransformedDataSource($datasourceInstance, $transformations, $parameterValues, $offset = null, $limit = null) {

        // Validate parameters first up
        $this->validateParameters($datasourceInstance, $transformations, $parameterValues);


        // Grab the data source for this instance
        $datasource = $datasourceInstance->returnDataSource();


        // Apply transformations
        $datasource = $this->applyTransformationsToDatasource($datasource, $transformations ?? [], $parameterValues, $offset, $limit);

        return [$datasource, $parameterValues];
    }


    /**
     * Check if an import key is available for a datasource instance passed in by key.
     *
     * @param $datasourceInstanceKey
     * @param $proposedImportKey
     *
     * @return boolean
     */
    public function importKeyAvailableForDatasourceInstance($datasourceInstanceKey, $proposedImportKey) {
        $instance = $this->datasourceDAO->getDataSourceInstanceByKey($datasourceInstanceKey);
        return $this->datasourceDAO->importKeyAvailableForDatasourceInstance($instance, $proposedImportKey);
    }


    /**
     * Update a datasource instance using a passed datasource key and update object.  This variant allows for insecure
     * use if required
     *
     * @param string $datasourceInstanceKey
     * @param DatasourceUpdate $datasourceUpdate
     *
     * @return DatasourceUpdateResult
     */
    public function updateDatasourceInstanceByKey($datasourceInstanceKey, $datasourceUpdate, $allowInsecure = false) {

        // Grab the instance and call the child function
        $datasourceInstance = $this->getDataSourceInstanceByKey($datasourceInstanceKey);
        return $this->updateDatasourceInstance($datasourceInstance, $datasourceUpdate, $allowInsecure);

    }


    /**
     * Update a datasource instance by import key, qualified optionally by project key and account id
     *
     * @param string $importKey
     * @param DatasourceUpdate $datasourceUpdate
     * @param string $projectKey
     * @param int $accountId
     *
     * @return DatasourceUpdateResult
     */
    public function updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Grab the instance and call the child function
        $datasourceInstance = $this->datasourceDAO->getDatasourceInstanceByImportKey($importKey, $accountId);
        return $this->updateDatasourceInstance($datasourceInstance, $datasourceUpdate, false);
    }


    /**
     * Delete from Datasource Instance by key using a filter junction to determine records to delete
     *
     * @param string $datasourceInstanceKey
     * @param FilterJunction $filterJunction
     */
    public function filteredDeleteFromDatasourceInstanceByKey($datasourceInstanceKey, $filterJunction) {

        // Grab instance, resolve the datasource and issue a filtered delete
        $datasourceInstance = $this->datasourceDAO->getDataSourceInstanceByKey($datasourceInstanceKey);
        list($hasManagePrivilege, $datasource) = $this->getDatasourceFromInstance($datasourceInstance);
        $datasource->filteredDelete($filterJunction);
    }


    /**
     * Delete from Datasource Instance by key using a filter junction to determine records to delete
     *
     * @param string $datasourceInstanceKey
     * @param FilterJunction $filterJunction
     */
    public function filteredDeleteFromDatasourceInstanceByImportKey($importKey, $filterJunction, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Grab instance, resolve the datasource and issue a filtered delete
        $datasourceInstance = $this->datasourceDAO->getDatasourceInstanceByImportKey($importKey, $accountId);
        list($hasManagePrivilege, $datasource) = $this->getDatasourceFromInstance($datasourceInstance);
        $datasource->filteredDelete($filterJunction);
    }


    /**
     * Update a datasource instance, called from a wrapper above.
     *
     * @param DatasourceInstance $datasourceInstance
     * @param DatasourceUpdate $datasourceUpdate
     * @param boolean $allowInsecure
     *
     * @return DatasourceUpdateResult
     *
     * @throws DatasourceNotUpdatableException
     * @throws ObjectNotFoundException
     */
    private function updateDatasourceInstance($datasourceInstance, $datasourceUpdate, $allowInsecure = false) {

        list($hasManagePrivilege, $datasource) = $this->getDatasourceFromInstance($datasourceInstance, $allowInsecure);


        // If a structural update also apply structural stuff
        if ($datasourceUpdate instanceof DatasourceUpdateWithStructure) {

            if ($datasourceInstance->getProjectKey() && !$hasManagePrivilege) {
                throw new AccessDeniedException("You have not been granted access to manage custom data sources");
            }

            $datasourceInstance->setTitle($datasourceUpdate->getTitle());
            $datasourceInstance->setImportKey($datasourceUpdate->getImportKey());

            // If updatable and fields
            if ($datasource instanceof UpdatableDatasource) {

                // Update configuration of data source.
                $config = $datasource->getConfig();

                if ($datasourceUpdate->getFields()) {
                    $config->setColumns($datasourceUpdate->getFields());
                    $datasourceInstance->setConfig($config);
                }

                if ($config instanceof IndexableDatasourceConfig) {
                    $config->setIndexes($datasourceUpdate->getIndexes());
                    $datasourceInstance->setConfig($config);
                }

            }

            $this->saveDataSourceInstance($datasourceInstance);

        }

        $result = null;

        // Perform the various updates required
        if ($datasourceUpdate->getAdds()) {
            if ($datasource->getConfig()->getColumns()) {
                $fields = [];
                foreach ($datasource->getConfig()->getColumns() as $column) {
                    if ($column->getType() !== Field::TYPE_ID)
                        $fields[] = $column;
                }
            } else {
                $fields = array_map(function ($columnName) {
                    return new Field($columnName);
                }, array_keys($datasourceUpdate->getAdds()[0]));
            }
            $result = $datasource->update(new ArrayTabularDataset($fields, $datasourceUpdate->getAdds()), UpdatableDatasource::UPDATE_MODE_ADD);
        }

        if ($datasourceUpdate->getUpdates()) {
            $fields = $datasource->getConfig()->getColumns() ?: array_map(function ($columnName) {
                return new Field($columnName);
            }, array_keys($datasourceUpdate->getUpdates()[0]));
            $updateResult = $datasource->update(new ArrayTabularDataset($fields, $datasourceUpdate->getUpdates()), UpdatableDatasource::UPDATE_MODE_UPDATE);
            $result ? $result->combine($updateResult) : $result = $updateResult;
        }


        if ($datasourceUpdate->getDeletes()) {
            $fields = $datasource->getConfig()->getColumns() ?: array_map(function ($columnName) {
                return new Field($columnName);
            }, array_keys($datasourceUpdate->getDeletes()[0]));
            $deleteResult = $datasource->update(new ArrayTabularDataset($fields, $datasourceUpdate->getDeletes()), UpdatableDatasource::UPDATE_MODE_DELETE);
            $result ? $result->combine($deleteResult) : $result = $deleteResult;
        }

        if ($datasourceUpdate->getReplaces()) {
            $fields = $datasource->getConfig()->getColumns() ?: array_map(function ($columnName) {
                return new Field($columnName);
            }, array_keys($datasourceUpdate->getReplaces()[0]));
            $replaceResult = $datasource->update(new ArrayTabularDataset($fields, $datasourceUpdate->getReplaces()), UpdatableDatasource::UPDATE_MODE_REPLACE);
            $result ? $result->combine($replaceResult) : $result = $replaceResult;
        }

        return $result;

    }


    /**
     * @param $allowInsecure
     * @param DatasourceInstance $datasourceInstance
     * @return array
     * @throws AccessDeniedException
     * @throws DatasourceNotUpdatableException
     * @throws ObjectNotFoundException
     * @throws ValidationException
     * @throws \Kiniauth\Exception\Security\MissingScopeObjectIdForPrivilegeException
     * @throws \Kiniauth\Exception\Security\NonExistentPrivilegeException
     */
    private function getDatasourceFromInstance(DatasourceInstance $datasourceInstance, $allowInsecure = false) {
        if (!$allowInsecure && ($datasourceInstance->getAccountId() == null && !$this->securityService->isSuperUserLoggedIn())) {
            throw new ObjectNotFoundException(DatasourceInstance::class, $datasourceInstance->getKey());
        }

        // Check privileges if a project key
        $hasManagePrivilege = false;
        if (!$allowInsecure && $datasourceInstance->getProjectKey()) {
            $hasUpdatePrivilege = $this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_PROJECT, "customdatasourceupdate", $datasourceInstance->getProjectKey());
            $hasManagePrivilege = $this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_PROJECT, "customdatasourcemanage", $datasourceInstance->getProjectKey());

            if ((!$hasUpdatePrivilege && !$hasManagePrivilege))
                throw new AccessDeniedException("You have not been granted access to update custom data sources");
        }

        // Grab the datasource.
        $datasource = $datasourceInstance->returnDataSource();


        if (!($datasource instanceof UpdatableDatasource)) {
            throw new DatasourceNotUpdatableException($datasource);
        }
        return array($hasManagePrivilege, $datasource);
    }


    /**
     * Apply the transformation instances to the supplied data source and return
     * a new datasource.
     *
     * @param Datasource $datasource
     * @param TransformationInstance[] $transformationInstances
     */
    private function applyTransformationsToDatasource($datasource, $transformationInstances, $parameterValues, $offset = null, $limit = null) {


        if ($offset !== null && $limit !== null)
            $pagingTransformation = new PagingTransformation($limit, $offset);
        else $pagingTransformation = null;

        foreach ($transformationInstances as $transformationInstance) {

            $transformation = $transformationInstance->returnTransformation();

            // If a marker transformation, use a paging transformation instead
            if ($transformation instanceof PagingMarkerTransformation) {

                // If no paging transformation, ignore this one
                if (!$pagingTransformation) continue;

                $transformation = $pagingTransformation;
                $pagingTransformation->setApplied(true);
            }

            if ($this->isTransformationSupported($datasource, $transformation)) {
                $datasource = $datasource->applyTransformation($transformation, $parameterValues, $pagingTransformation);
            } else if ($datasource instanceof DefaultDatasource) {
                throw new UnsupportedDatasourceTransformationException($datasource, $transformation);
            } else {
                $defaultDatasource = new DefaultDatasource($datasource);
                if ($this->isTransformationSupported($defaultDatasource, $transformation))
                    $datasource = $defaultDatasource->applyTransformation($transformation, $parameterValues, $pagingTransformation);
                else
                    throw new UnsupportedDatasourceTransformationException($datasource, $transformation);
            }

        }

        // If no paging marker found and paging is supported as a transformation apply offset and limit
        if ($pagingTransformation && (!$pagingTransformation->isApplied()) && $this->isTransformationSupported($datasource, $pagingTransformation)) {
            $datasource = $datasource->applyTransformation($pagingTransformation, $parameterValues);
        }

        return $datasource;
    }

    // Check whether a transformation is supported by a datasource
    private function isTransformationSupported($datasource, $transformation) {
        foreach ($datasource->getSupportedTransformationClasses() ?? [] as $supportedTransformationClass) {
            if (is_a($transformation, $supportedTransformationClass) || is_subclass_of($transformation, $supportedTransformationClass)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Ensure all required parameters are supplied
     *
     * @param DatasourceInstance $datasourceInstance
     * @param TransformationInstance[] $transformationInstances
     *
     * @param mixed[] $parameterValues
     */
    private function validateParameters($datasourceInstance, $transformationInstances, &$parameterValues) {

        $validationErrors = [];

        // Grab parameters from data source instance
        $parameters = $datasourceInstance->getParameters() ?? [];


        // Check that the parameters are supplied and of required type
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            if (!isset($parameterValues[$paramName])) {

                if ($parameter->getDefaultValue()) {
                    $parameterValues[$paramName] = $this->valueFunctionEvaluator->evaluateSpecialExpressions($parameter->getDefaultValue());
                } else {
                    $validationErrors[$paramName] = [
                        "required" => new FieldValidationError($paramName, "required", "Parameter {$paramName} is required")];
                }
            } else {
                $parameterValues[$paramName] = $this->valueFunctionEvaluator->evaluateSpecialExpressions($parameterValues[$paramName]);
                if (!$parameter->validateParameterValue($parameterValues[$paramName])) {
                    $validationErrors[$paramName] = [
                        new FieldValidationError($paramName, "wrongtype", "Parameter {$paramName} is of the wrong type - should be {$parameter->getType()}")
                    ];
                }
            }
        }

        // Throw exception if at least one validation error
        if (sizeof($validationErrors) > 0) {
            throw new InvalidParametersException($validationErrors);
        }

        // Add in the logged in account id if available

        /**
         * @var Account $account
         */
        $account = $this->securityService->getLoggedInSecurableAndAccount()[1] ?? null;
        if ($account)
            $parameterValues["ACCOUNT_ID"] = $account->getAccountId();


        return $parameterValues;

    }


}
