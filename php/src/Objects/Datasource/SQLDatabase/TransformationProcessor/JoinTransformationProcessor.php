<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterJunctionEvaluator;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class JoinTransformationProcessor implements SQLTransformationProcessor {

    /**
     * @var DatasourceService
     */
    private $datasourceService;


    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * Table index
     *
     * @var int
     */
    private $tableIndex = 0;


    /**
     * Alias index
     *
     * @var int
     */
    private $aliasIndex = 0;


    /**
     * JoinTransformationProcessor constructor.
     *
     * @param DatasourceService $datasourceService
     * @param DatasetService $datasetService
     */
    public function __construct($datasourceService, $datasetService) {
        $this->datasourceService = $datasourceService;
        $this->datasetService = $datasetService;
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
            if ($transformation->getJoinFilters()) {
                $evaluator = new SQLFilterJunctionEvaluator($childTableAlias, $mainTableAlias);
                $evaluated = $evaluator->evaluateFilterJunctionSQL($transformation->getJoinFilters(), $parameterValues);
                $joinCriteria = $evaluated["sql"];
                $joinParameters = $evaluated["parameters"];
            }

            // Aggregate all parameters for join query
            $allParameters = array_merge($query->getParameters(), $childQuery->getParameters(), $joinParameters);


            // If join columns supplied, change the select query for selection
            $childSelectColumns = $childTableAlias . ".*";
            if ($transformation->getJoinColumns()) {
                $joinColumnStrings = [];
                foreach ($transformation->getJoinColumns() as $joinColumn) {
                    $joinColumnStrings[] = $childTableAlias . "." . $joinColumn->getName() . " alias_" . ++$this->aliasIndex;
                }
                $childSelectColumns = join(",", $joinColumnStrings);
            }

            // Create the join query
            $joinQuery = new SQLQuery("$mainTableAlias.*,$childSelectColumns", "({$query->getSQL()}) $mainTableAlias INNER JOIN ({$childQuery->getSQL()}) $childTableAlias ON {$joinCriteria}", $allParameters);

            return $joinQuery;
        } else {
            return $query;
        }


    }


}