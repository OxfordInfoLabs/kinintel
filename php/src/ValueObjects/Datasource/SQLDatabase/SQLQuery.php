<?php


namespace Kinintel\ValueObjects\Datasource\SQLDatabase;


class SQLQuery {

    /**
     * @var string
     */
    private $sql;

    /**
     * @var mixed[]
     */
    private $parameters;

    /**
     * Query constructor.
     * @param string $sql
     * @param mixed[] $parameters
     */
    public function __construct($sql, $parameters = []) {
        $this->sql = $sql;
        $this->parameters = $parameters;
    }


    /**
     * @return string
     */
    public function getSql() {
        return $this->sql;
    }

    /**
     * @param string $sql
     */
    public function setSql($sql) {
        $this->sql = $sql;
    }

    /**
     * @return mixed[]
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @param mixed[] $parameters
     */
    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }


    /**
     * Add parameter function
     *
     * @param $parameter
     */
    public function addParameter($parameter) {
        $this->parameters[] = $parameter;
    }

}