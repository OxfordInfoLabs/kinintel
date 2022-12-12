<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterJunctionEvaluator;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;


class FilterTransformationProcessor extends SQLTransformationProcessor {


    /**
     * @var TemplateParser
     */
    private $templateParser;


    /**
     * @var integer
     */
    private $aliasIndex = 0;

    /**
     * FilterTransformationProcessor constructor.
     *
     * @param TemplateParser $templateParser
     */
    public function __construct($templateParser) {
        $this->templateParser = $templateParser;
    }

    /**
     * Update the passed query and return another query
     *
     * @param FilterTransformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param $dataSource
     *
     * @return SQLQuery
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {

        $evaluator = new SQLFilterJunctionEvaluator(null, null, $dataSource->returnDatabaseConnection());

        $evaluated = $evaluator->evaluateFilterJunctionSQL($transformation, $parameterValues);

        // Wrap query if required
        if ($this->queryWrapRequired($dataSource, $transformation)) {
            $query = new SQLQuery("*", "(" . $query->getSQL() . ") E" . ++$this->aliasIndex, $query->getParameters());
        }

        if ($query->hasGroupByClause()) {

            $sql = $evaluated["sql"];
            $params = $evaluated["parameters"];
            if ($havingClause = $query->getHavingClause()) {
                $sql = "(" . $havingClause . ") AND (" . $sql . ")";
                $params = array_merge($query->getParametersByClauseType(SQLQuery::HAVING_CLAUSE), $params);
            }

            $query->setHavingClause($sql, $params);
        } else {

            $sql = $evaluated["sql"];
            $params = $evaluated["parameters"];
            if ($whereClause = $query->getWhereClause()) {
                $sql = "(" . $whereClause . ") AND (" . $sql . ")";
                $params = array_merge($query->getParametersByClauseType(SQLQuery::WHERE_CLAUSE), $params);
            }

            $query->setWhereClause($sql, $params);
        }

        return $query;

    }

    /**
     * Return boolean indicator as to whether we need to wrap the query or not.
     *
     * @param Datasource $dataSource
     * @param Transformation $transformation
     */
    private function queryWrapRequired($dataSource, $transformation) {
        $datasourceTransformations = $dataSource->returnTransformations();
        $transformationIndex = array_search($transformation, $datasourceTransformations ?? []);
        $wrapRequired = false;
        for ($i = $transformationIndex - 1; $i >= 0; $i--) {
            $previousTransformation = $datasourceTransformations[$i];
            // We can stop at summarisations or joins as these will create sub queries anyway.
            if ($previousTransformation instanceof SummariseTransformation || $previousTransformation instanceof JoinTransformation)
                break;
            if ($previousTransformation instanceof FormulaTransformation) {
                $wrapRequired = true;
                break;
            }
        }
        return $wrapRequired;
    }

}