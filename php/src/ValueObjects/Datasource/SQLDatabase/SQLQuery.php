<?php


namespace Kinintel\ValueObjects\Datasource\SQLDatabase;


use Kinikit\Core\Logging\Logger;

class SQLQuery {

    /**
     * @var string
     */
    private $selectClause;

    /**
     * @var string
     */
    private $fromClause;

    /**
     * @var mixed[]
     */
    private $initialParameters;


    /**
     * Store clauses by type
     *
     * @var array
     */
    private $clausesByType = [];

    /**
     * Store parameters by clause type
     *
     * @var array
     */
    private $parametersByClauseType = [];


    // Clause constants
    const SELECT_CLAUSE = "SELECT";
    const WHERE_CLAUSE = "WHERE";
    const GROUP_BY_CLAUSE = "GROUP BY";
    const HAVING_CLAUSE = "HAVING";
    const ORDER_BY_CLAUSE = "ORDER BY";
    const OFFSET_CLAUSE = "OFFSET";
    const LIMIT_CLAUSE = "LIMIT";


    /**
     * Query constructor.
     * @param string $selectClause
     * @param mixed[] $parameters
     */
    public function __construct($selectClause, $fromClause, $initialParameters = []) {
        $this->selectClause = $selectClause;
        $this->fromClause = $fromClause;
        $this->initialParameters = $initialParameters;
    }


    /**
     * Return the select clause
     *
     * @return string
     */
    public function getSelectClause() {
        return $this->selectClause;
    }

    /**
     * @param string $selectClause
     */
    public function setSelectClause($selectClause, $parameters = []) {
        $this->selectClause = $selectClause;
        $this->parametersByClauseType[self::SELECT_CLAUSE] = $parameters;
    }


    /**
     * @return bool
     */
    public function hasGroupByClause() {
        return isset($this->clausesByType[self::GROUP_BY_CLAUSE]);
    }

    /**
     * Set the where clause for this query
     *
     * @param $clause
     * @param $parameters
     */
    public function setWhereClause($clause, $parameters = []) {
        $this->clausesByType[self::WHERE_CLAUSE] = $clause;
        $this->parametersByClauseType[self::WHERE_CLAUSE] = $parameters;
    }


    /**
     * Set the group by clause for this query
     *
     * @param $clause
     * @param $parameters
     */
    public function setGroupByClause($selectClause, $groupByClause, $parameters = []) {
        $this->selectClause = $selectClause;
        $this->clausesByType[self::GROUP_BY_CLAUSE] = $groupByClause;
        $this->parametersByClauseType[self::GROUP_BY_CLAUSE] = $parameters;

        // Unset any ordering or window parameters
        unset($this->clausesByType[self::ORDER_BY_CLAUSE]);
        unset($this->parametersByClauseType[self::ORDER_BY_CLAUSE]);
        unset($this->parametersByClauseType[self::OFFSET_CLAUSE]);
        unset($this->parametersByClauseType[self::LIMIT_CLAUSE]);


    }


    /**
     * Set the having clause for this query
     *
     * @param $clause
     * @param $parameters
     */
    public function setHavingClause($clause, $parameters) {
        if ($this->hasGroupByClause()) {
            $this->clausesByType[self::HAVING_CLAUSE] = $clause;
            $this->parametersByClauseType[self::HAVING_CLAUSE] = $parameters;
        }
    }


    /**
     * Set the order by clause for this query
     *
     * @param $clause
     * @param $parameters
     */
    public function setOrderByClause($clause, $parameters = []) {
        $this->clausesByType[self::ORDER_BY_CLAUSE] = $clause;
        $this->parametersByClauseType[self::ORDER_BY_CLAUSE] = $parameters;
    }

    /**
     * Set offset for query
     *
     * @param integer $offset
     */
    public function setOffset($offset) {
        $this->parametersByClauseType[self::OFFSET_CLAUSE] = [$offset];
    }


    /**
     * Set limit for query
     *
     * @param integer $limit
     */
    public function setLimit($limit) {
        $this->parametersByClauseType[self::LIMIT_CLAUSE] = [$limit];
    }


    /**
     * Return the evaluated SQL
     *
     * @return string
     */
    public function getSQL() {

        $sql = "SELECT " . $this->selectClause . " FROM " . $this->fromClause;
        if (isset($this->clausesByType[self::WHERE_CLAUSE])) {
            $sql .= " WHERE " . $this->clausesByType[self::WHERE_CLAUSE];
        }
        if (isset($this->clausesByType[self::GROUP_BY_CLAUSE])) {
            $sql .= " GROUP BY " . $this->clausesByType[self::GROUP_BY_CLAUSE];
        }
        if (isset($this->clausesByType[self::HAVING_CLAUSE])) {
            $sql .= " HAVING " . $this->clausesByType[self::HAVING_CLAUSE];
        }
        if (isset($this->clausesByType[self::ORDER_BY_CLAUSE])) {
            $sql .= " ORDER BY " . $this->clausesByType[self::ORDER_BY_CLAUSE];
        }
        if (isset($this->parametersByClauseType[self::LIMIT_CLAUSE])) {
            $sql .= " LIMIT ?";
        }
        if (isset($this->parametersByClauseType[self::OFFSET_CLAUSE])) {
            $sql .= " OFFSET ?";
        }

        Logger::log($sql);
        return $sql;

    }


    /**
     * Return parameters in positional order
     *
     * @return mixed[]
     */
    public function getParameters() {

        // Construct parameters array in correct order
        $parameters = $this->initialParameters;
        $parameters = array_merge($parameters, $this->parametersByClauseType[self::SELECT_CLAUSE] ?? []);
        $parameters = array_merge($parameters, $this->parametersByClauseType[self::WHERE_CLAUSE] ?? []);
        $parameters = array_merge($parameters, $this->parametersByClauseType[self::GROUP_BY_CLAUSE] ?? []);
        $parameters = array_merge($parameters, $this->parametersByClauseType[self::HAVING_CLAUSE] ?? []);
        $parameters = array_merge($parameters, $this->parametersByClauseType[self::ORDER_BY_CLAUSE] ?? []);
        $parameters = array_merge($parameters, $this->parametersByClauseType[self::LIMIT_CLAUSE] ?? []);
        $parameters = array_merge($parameters, $this->parametersByClauseType[self::OFFSET_CLAUSE] ?? []);

        return $parameters;
    }


}