<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kiniauth\Services\Util\Asynchronous\AMPAsyncAsynchronousProcessor;
use Kiniauth\Services\Util\Asynchronous\AMPParallelTask;
use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Asynchronous\AsynchronousClassMethod;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Controllers\Internal\ProcessedDataset;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterJunctionEvaluator;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\ProcessedTabularDataSet;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;

class JoinTransformationProcessor extends SQLTransformationProcessor {

    private int $tableIndex = 0;
    private int $subQueryIndex = 0;
    private int $aliasIndex = 0;

    public function __construct(
        private DatasourceService $datasourceService,
        private DatasetService $datasetService,
        private AsynchronousProcessor $synchronousProcessor,
        private AsynchronousProcessor $parallelProcessor,
    ) {
    }

    /**
     * @param JoinTransformation $transformation
     * @param Datasource $datasource
     * @param mixed[] $parameterValues
     * @param null $pagingTransformation //todo type??
     *
     * @return \Kinintel\Objects\Datasource\Datasource|DefaultDatasource|mixed
     * @throws \Kinikit\Core\Validation\ValidationException
     * @throws \Kinikit\Persistence\ORM\Exception\ObjectNotFoundException
     * @throws \Kinintel\Exception\InvalidParametersException
     * @throws \Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException
     * @throws \Kinintel\Exception\UnsupportedDatasourceTransformationException
     */
    public function applyTransformation($transformation, $datasource, $parameterValues = [], $pagingTransformation = null) {

        $joinDataParameters = [];

        // Triage to see whether we can read the evaluated data source
        if ($transformation->returnEvaluatedDataSource()) {
            $joinDatasource = $transformation->returnEvaluatedDataSource();
        } else if ($transformation->getJoinedDataSourceInstanceKey()) {
            $this->datasourceService = $this->datasourceService ?? Container::instance()->get(DatasourceService::class);
            $joinDatasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($transformation->getJoinedDataSourceInstanceKey());
            $joinDataParameters = $joinDatasourceInstance->getParameters();
            $joinDatasource = $joinDatasourceInstance->returnDataSource();
        } else if ($transformation->getJoinedDataSetInstanceId()) {
            $this->datasetService = $this->datasetService ?? Container::instance()->get(DatasetService::class);
            $joinDataSet = $this->datasetService->getDataSetInstance($transformation->getJoinedDataSetInstanceId());

            // If parameters required for a data set, ensure we have mappings for them.
            $joinDataParameters = $this->datasetService->getEvaluatedParameters($joinDataSet);

        }

        // If we have join data parameters, evaluate now.
        $paramParameters = [];
        $columnParameters = [];

        if (is_array($joinDataParameters) && sizeof($joinDataParameters)) {
            list($paramParameters, $columnParameters) = $this->processJoinParameterMappings($transformation, $joinDataParameters, $parameterValues);
        }

        // Boolean indicating whether or not we need to convert the join datasource to
        // a default datasource
        $joinDatasourceConversionRequired = false;
        $joinDatasourceParameterValues = $parameterValues;

        // If we have param parameters, materialise to a
        if (sizeof($paramParameters) || sizeof($columnParameters)) {
            $joinDatasourceConversionRequired = true;
            $joinDatasourceParameterValues = $paramParameters;


        } else {
            if (isset($joinDataSet)) {
                $joinDatasource = $this->datasetService->getTransformedDatasourceForDataSetInstance($joinDataSet, $parameterValues,
                    []);
            }
            // Update the transformation with the evaluated data source.
            $transformation->setEvaluatedDataSource($joinDatasource);

        }

        // Calculate whether or not we need to perform join datasource conversion
        // to default datasource
        $joinDatasourceConversionRequired = $joinDatasourceConversionRequired || (
                ($joinDatasource->getAuthenticationCredentials() != $datasource->getAuthenticationCredentials()) &&
                !($joinDatasource instanceof DefaultDatasource)
            );


        // If we need to convert the join datasource, do this now
        if ($joinDatasourceConversionRequired) {

            // If we have column parameters we need to do a more advanced evaluation
            if ($columnParameters) {

                // Pre-columns
                $preColumns = $datasource->getConfig()->getColumns();

                // If a paging transformation and not yet applied, apply this now
                if ($pagingTransformation instanceof PagingTransformation && !$pagingTransformation->isApplied()) {
                    $datasource = $datasource->applyTransformation($pagingTransformation, $parameterValues);
                    $pagingTransformation->setApplied(true);
                }

                // Materialise the parent data set
                $parentDataset = $datasource->materialise($parameterValues);

                // Reset columns
                $datasource->getConfig()->setColumns($preColumns);

                $aliasFields = [];
                $joinFilters = [];
                foreach ($columnParameters as $parameterName => $columnName) {
                    $aliasField = "alias_" . ++$this->aliasIndex;
                    $aliasFields[$parameterName] = $aliasField;
                    $joinFilters[] = new Filter("[[$columnName]]", "[[" . $aliasField . "]]");
                }

                $joinFilterJunction = new FilterJunction($joinFilters, $transformation->getJoinFilters()
                && (sizeof($transformation->getJoinFilters()->getFilterJunctions()) || sizeof($transformation->getJoinFilters()->getFilters()))
                    ? [$transformation->getJoinFilters()] : []);
                $transformation->setJoinFilters($joinFilterJunction);


                // - Run single threaded if joinConcurrency is set to max in config
                // - Check a static global to see if we're in a thread to prevent nested threads.
                $joinConcurrency = Configuration::readParameter("sqldatabase.datasource.join.default.concurrency") ?? PHP_INT_MAX;

                /** @var AsynchronousProcessor $processor */
                if ($joinConcurrency == PHP_INT_MAX) {
                    $processor = $this->synchronousProcessor;
                } else if (!AMPParallelTask::$inParallel) {
                    $processor = $this->parallelProcessor;
                } else {
                    $processor = Container::instance()->get(AMPAsyncAsynchronousProcessor::class);
                }

                // Now materialise the join data set using column values from parent dataset
                $newJoinData = [];
                $asyncInstances = [];
                $parentValues = [];
                $joinColumns = [];
                while ($parentRow = $parentDataset->nextDataItem()) {


                    // Set any column parameters accordingly
                    $columnValues = [];
                    foreach ($columnParameters as $parameterName => $columnName) {
                        $joinDatasourceParameterValues[$parameterName] = $columnValues[$aliasFields[$parameterName]] = $parentRow[$columnName] ?? null;
                    }

                    // Add to stack of asynchronous
                    if (isset($joinDataSet)) {

                        $asyncInstances[] = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
                            "dataSetInstance" => $joinDataSet, "parameterValues" => $joinDatasourceParameterValues
                        ]);
                    } else if (isset($joinDatasourceInstance)) {
                        $asyncInstances[] = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasourceInstance", [
                            "datasourceInstance" => $joinDatasourceInstance, "parameterValues" => $joinDatasourceParameterValues
                        ]);
                    }

                    // Stash parent values for later
                    $parentValues[] = $columnValues;


                    // If we reached limit, process rows and reset variables
                    if (sizeof($asyncInstances) == $joinConcurrency) {
                        $joinColumns = $this->processAsyncInstances($processor, $asyncInstances, $parentValues, $newJoinData) ?? $joinColumns;
                        $asyncInstances = [];
                        $parentValues = [];
                    }

                }

                // Process the remaining instances
                if (sizeof($asyncInstances)) {
                    $joinColumns = $this->processAsyncInstances($processor, $asyncInstances, $parentValues, $newJoinData) ?? $joinColumns;
                }

                // Derive new columns for join dataset or skip entirely if no join columns to create
                $newColumns = [];
                $joinColumns = $joinColumns ?: $transformation->getJoinColumns() ?: [];
                if (sizeof($joinColumns)) {

                    foreach ($aliasFields as $aliasField) {
                        $newColumns[] = new Field($aliasField);
                    }
                    $newColumns = array_merge($newColumns, Field::toPlainFields($joinColumns));

                    // Create new join dataset.
                    $newJoinDataset = new ArrayTabularDataset($newColumns, $newJoinData);

                    $joinDatasource = new DefaultDatasource($newJoinDataset);
                } else {
                    return $datasource;
                }

            } else {

                if (isset($joinDataSet) && !isset($joinDatasource)) {


                    $joinDatasource = $this->datasetService->getTransformedDatasourceForDataSetInstance($joinDataSet, $joinDatasourceParameterValues,
                        []);

                }

                $joinDatasource = new DefaultDatasource($joinDatasource);

            }


            $joinDatasource->populate($joinDatasourceParameterValues);


            // If no join columns supplied, set them up now.
            if (!$transformation->getJoinColumns()) {
                $transformation->setJoinColumns(array_slice($joinDatasource->getConfig()->getColumns(), sizeof($columnParameters)));
            }

            $transformation->setEvaluatedDataSource($joinDatasource);
        }


        // If we are not a default datasource already, return a new instance
        if (($joinDatasource->getAuthenticationCredentials() != $datasource->getAuthenticationCredentials()) && !($datasource instanceof DefaultDatasource)) {
            $newDataSource = new DefaultDatasource($datasource);
            $datasource = $newDataSource->applyTransformation($transformation, $parameterValues);
        }


        // For a join transformation, if join columns are supplied we must have master datasource columns as well
        if ($transformation->getJoinColumns() && !$datasource->getConfig()->getColumns()) {

            // Add a paging transformation to make the query efficient
            $datasource->applyTransformation(new PagingTransformation(1));

            // Materialise the set
            $dataSet = $datasource->materialise($parameterValues);

            // Remove the redundant Paging Transformation
            $datasource->unapplyLastTransformation();

            $datasource->getConfig()->setColumns($dataSet->getColumns());
        }

        return $datasource;

    }


    /**
     * Update a SQL query object for a join transformation.
     *
     * @param JoinTransformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param $dataSource
     *
     * @return SQLQuery|void
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {

        // Ensure we have an evaluated datasource before continuing
        $joinDatasource = $transformation->returnEvaluatedDataSource();

        // If we have a child query, use this to generate a new query using the various criteria.
        if ($joinDatasource instanceof SQLDatabaseDatasource &&
            ($joinDatasource->getAuthenticationCredentials() == $dataSource->getAuthenticationCredentials())) {
            $childQuery = $joinDatasource->buildQuery($parameterValues);


            // Calculate the new aliases
            $mainTableAlias = "T" . ++$this->tableIndex;
            $childTableAlias = "T" . ++$this->tableIndex;

            // Evaluate join criteria if supplied
            $joinCriteria = "1 = 1";
            $joinParameters = [];


            if ($transformation->getJoinFilters() && (sizeof($transformation->getJoinFilters()->getFilters()) || sizeof($transformation->getJoinFilters()->getFilterJunctions()))) {

                $evaluator = new SQLFilterJunctionEvaluator($mainTableAlias, $childTableAlias, $dataSource->returnDatabaseConnection());
                $evaluated = $evaluator->evaluateFilterJunctionSQL($transformation->getJoinFilters(), $parameterValues);
                $joinCriteria = $evaluated["sql"];
                $joinParameters = $evaluated["parameters"];
            }

            // Aggregate all parameters for join query
            $allParameters = array_merge($query->getParameters(), $childQuery->getParameters(), $joinParameters);


            // If join columns supplied, change the select query for selection
            $childSelectColumns = $childTableAlias . ".*";


            if ($transformation->getJoinColumns()) {

                $newColumns = $dataSource->getConfig()->getColumns() ?? [];

                // Get column names
                $indexedColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $newColumns);

                // Create the SQL fragments and new column mappings.
                $joinColumnStrings = [];
                foreach ($transformation->getJoinColumns() as $joinColumn) {

                    if (isset($indexedColumns[$joinColumn->getName()])) {
                        $i = 2;
                        while (isset($indexedColumns[$joinColumn->getName() . "_$i"])) $i++;
                        $columnName = $joinColumn->getName() . "_$i";
                        $columnSpec = $joinColumn->getName() . " " . $joinColumn->getName() . "_$i";
                    } else {
                        $columnName = $joinColumn->getName();
                        $columnSpec = $columnName;
                    }


                    $joinColumnStrings[] = $childTableAlias . "." . $columnSpec;
                    $newColumns[] = new Field($columnName, $joinColumn->getTitle(), null, $joinColumn->getType());
                }


                $childSelectColumns = join(",", $joinColumnStrings);
                $dataSource->getConfig()->setColumns($newColumns);
            }

            $subQueryIndex = ++$this->subQueryIndex;


            // Create the join query
            $joinType = $transformation->isStrictJoin() ? "INNER" : "LEFT";
            $joinQuery = new SQLQuery("*", "(SELECT $mainTableAlias.*,$childSelectColumns FROM ({$query->getSQL()}) $mainTableAlias $joinType JOIN ({$childQuery->getSQL()}) $childTableAlias ON {$joinCriteria}) S$subQueryIndex", $allParameters);

            return $joinQuery;
        } else {

            return $query;
        }


    }


    /**
     * Process join parameter mappings for transformation
     *
     * @param JoinTransformation $transformation
     * @param Parameter[] $joinDataParameters
     */
    private function processJoinParameterMappings($transformation, $joinDataParameters, $parameterValues = []) {

        // Get the parameter mappings as an indexed array
        $joinParameterMappings = ObjectArrayUtils::indexArrayOfObjectsByMember("parameterName", $transformation->getJoinParameterMappings() ?? []);


        if ($transformation->getJoinedDataSetInstanceId()) {
            $scope = "dataset";
            $member = "id";
            $value = $transformation->getJoinedDataSetInstanceId();
        } else {
            $scope = "datasource";
            $member = "key";
            $value = $transformation->getJoinedDataSourceInstanceKey();
        }


        $columnParameters = [];
        $paramParameters = [];


        // If parameters required for a data source, ensure that we have received mappings for them.
        foreach ($joinDataParameters as $datasourceParam) {
            if (!isset($joinParameterMappings[$datasourceParam->getName()])) {
                throw new DatasourceTransformationException("Parameter mapping required for parameter {$datasourceParam->getName()} when adding the $scope with $member $value using the join operation.");
            }

            $mapping = $joinParameterMappings[$datasourceParam->getName()];

            // if a source parameter mapping, bind directly to incoming parameter
            if ($mapping->getSourceParameterName() && isset($parameterValues[$mapping->getSourceParameterName()])) {
                $paramParameters[$mapping->getParameterName()] = $parameterValues[$mapping->getSourceParameterName()];
            } else if ($mapping->getSourceColumnName()) {
                $columnParameters[$mapping->getParameterName()] = $mapping->getSourceColumnName();
            }

        }

        return [$paramParameters, $columnParameters];

    }

    /**
     * @param AsynchronousProcessor $processor
     * @param Asynchronous[] $asyncInstances
     * @param mixed[] $parentValues
     * @param mixed[] $newJoinData
     */
    private function processAsyncInstances($processor, $asyncInstances, $parentValues, &$newJoinData) {

        $asyncInstances = $processor->executeAndWait($asyncInstances);
        if (!$asyncInstances) throw new \Exception("Failed to process async instances");

        $joinColumns = [];

        // Loop through each instance and create new join data
        foreach ($asyncInstances as $index => $asyncInstance) {

            // Synchronise join data
            if ($asyncInstance->getStatus() == Asynchronous::STATUS_COMPLETED) {
                /**
                 * @var ProcessedTabularDataSet $dataset
                 */
                $dataset = $asyncInstance->getReturnValue();

                // Derive new join data
                foreach ($dataset->getData() as $dataItem) {
                    $newJoinData[] = array_merge($parentValues[$index], $dataItem);
                }
                // Synchronise columns
                $joinColumns = $dataset->getColumns();
            } else {
                $newJoinData[] = $parentValues[$index];
            }

        }

        return $joinColumns;
    }

    public function setParallelProcessor(AsynchronousProcessor $parallelProcessor): void {
        $this->parallelProcessor = $parallelProcessor;
    }
}