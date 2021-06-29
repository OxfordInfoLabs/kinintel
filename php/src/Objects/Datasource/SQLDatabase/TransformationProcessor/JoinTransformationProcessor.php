<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
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
     * Table index
     *
     * @var int
     */
    private $tableIndex = 0;

    /**
     * Join clause index
     *
     * @var int
     */
    private $joinClauseIndex = 0;

    /**
     * JoinTransformationProcessor constructor.
     *
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;
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

        $mainQuery = $dataSource->buildQuery($parameterValues);

        // If this is a data source join, check to see whether we can directly join the datasource.
        if ($transformation->getJoinedDataSourceKey()) {
            $joinDatasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($transformation->getJoinedDataSourceKey());
            $joinDatasource = $joinDatasourceInstance->returnDataSource();

            if ($joinDatasource instanceof SQLDatabaseDatasource &&
                ($joinDatasource->getAuthenticationCredentials() === $dataSource->getAuthenticationCredentials())) {
                $childQuery = $joinDatasource->buildQuery($parameterValues);
            }
        }

        // If we have a child query, use this to generate a new query using the various criteria.
        if ($childQuery) {

            // Calculate the new aliases
            $mainTableAlias = "T" . ++$this->tableIndex;
            $childTableAlias = "T" . ++$this->tableIndex;

            // Grab the join criteria
            $joinCriteria = $this->createJoinCriteria($mainTableAlias, $childTableAlias, $transformation);

            // Create the join query
            $joinQuery = new SQLQuery("$mainTableAlias.*,$childTableAlias.*", "({$mainQuery->getSQL()}) $mainTableAlias INNER JOIN ({$childQuery->getSQL()}) $childTableAlias ON {$joinCriteria}");

            return $joinQuery;
        } else {
            return $mainQuery;
        }


    }

    /**
     * Create the join criteria
     *
     * @param string $mainTableAlias
     * @param string $childTableAlias
     * @param Transformation $transformation
     */
    private function createJoinCriteria($mainTableAlias, $childTableAlias, $transformation) {



    }


}