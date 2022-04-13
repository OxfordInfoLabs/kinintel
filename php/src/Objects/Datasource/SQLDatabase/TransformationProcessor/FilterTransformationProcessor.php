<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterJunctionEvaluator;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;


class FilterTransformationProcessor extends SQLTransformationProcessor {


    /**
     * @var TemplateParser
     */
    private $templateParser;


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


}