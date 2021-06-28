<?php


namespace Kinintel\ValueObjects\Transformation\Join;


use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class JoinTransformation implements Transformation, SQLDatabaseTransformation {


    /**
     * Key identifying a data source to join to.
     * This is either / or with a joined data set id.
     *
     * @var string
     * @requiredEither joinedDataSetId
     */
    private $joinedDataSourceKey;


    /**
     * Id of a data set to join to.  This is either / or with a
     * joined data source key
     *
     * @var integer
     */
    private $joinedDataSetId;


    /**
     * @var JoinParameterMapping[]
     */
    private $joinParameterMappings;


    /**
     * Join filters for filtering a joined data set referencing
     * columns on both sides of a join.
     *
     * @var FilterJunction
     */
    private $joinFilters;

    /**
     * JoinTransformation constructor.
     *
     * @param string $joinedDataSourceKey
     * @param int $joinedDataSetId
     * @param JoinParameterMapping[] $joinParameterMappings
     * @param FilterJunction $joinFilters
     */
    public function __construct($joinedDataSourceKey = null, $joinedDataSetId = null, $joinParameterMappings = [], $joinFilters = null) {
        $this->joinedDataSourceKey = $joinedDataSourceKey;
        $this->joinedDataSetId = $joinedDataSetId;
        $this->joinParameterMappings = $joinParameterMappings;
        $this->joinFilters = $joinFilters;
    }

    /**
     * @return string
     */
    public function getJoinedDataSourceKey() {
        return $this->joinedDataSourceKey;
    }

    /**
     * @param string $joinedDataSourceKey
     */
    public function setJoinedDataSourceKey($joinedDataSourceKey) {
        $this->joinedDataSourceKey = $joinedDataSourceKey;
    }

    /**
     * @return int
     */
    public function getJoinedDataSetId() {
        return $this->joinedDataSetId;
    }

    /**
     * @param int $joinedDataSetId
     */
    public function setJoinedDataSetId($joinedDataSetId) {
        $this->joinedDataSetId = $joinedDataSetId;
    }

    /**
     * @return mixed
     */
    public function getJoinParameterMappings() {
        return $this->joinParameterMappings;
    }

    /**
     * @param mixed $joinParameterMappings
     */
    public function setJoinParameterMappings($joinParameterMappings) {
        $this->joinParameterMappings = $joinParameterMappings;
    }

    /**
     * @return FilterJunction
     */
    public function getJoinFilters() {
        return $this->joinFilters;
    }

    /**
     * @param FilterJunction $joinFilters
     */
    public function setJoinFilters($joinFilters) {
        $this->joinFilters = $joinFilters;
    }


    public function getSQLTransformationProcessorKey() {
        return "join";
    }
}