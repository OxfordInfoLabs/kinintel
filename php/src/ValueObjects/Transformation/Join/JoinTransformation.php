<?php


namespace Kinintel\ValueObjects\Transformation\Join;


use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class JoinTransformation implements Transformation, SQLDatabaseTransformation {


    /**
     * Key identifying a data source to join to.
     * This is either / or with a joined data set id.
     *
     * @var string
     * @requiredEither joinedDataSetInstanceId
     */
    private $joinedDataSourceInstanceKey;


    /**
     * Id of a data set to join to.  This is either / or with a
     * joined data source key
     *
     * @var integer
     */
    private $joinedDataSetInstanceId;


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
     * Which columns are to be included - here is also an opportunity to rename them.
     *
     * @var Field[]
     */
    private $joinColumns = [];


    /**
     * JoinTransformation constructor.
     *
     * @param string $joinedDataSourceKey
     * @param int $joinedDataSetId
     * @param JoinParameterMapping[] $joinParameterMappings
     * @param FilterJunction $joinFilters
     */
    public function __construct($joinedDataSourceKey = null, $joinedDataSetId = null, $joinParameterMappings = [],
                                $joinFilters = null, $includedColumnNames = []) {
        $this->joinedDataSourceInstanceKey = $joinedDataSourceKey;
        $this->joinedDataSetInstanceId = $joinedDataSetId;
        $this->joinParameterMappings = $joinParameterMappings;
        $this->joinFilters = $joinFilters;
        $this->joinColumns = $includedColumnNames;
    }

    /**
     * @return string
     */
    public function getJoinedDataSourceInstanceKey() {
        return $this->joinedDataSourceInstanceKey;
    }

    /**
     * @param string $joinedDataSourceInstanceKey
     */
    public function setJoinedDataSourceInstanceKey($joinedDataSourceInstanceKey) {
        $this->joinedDataSourceInstanceKey = $joinedDataSourceInstanceKey;
    }

    /**
     * @return int
     */
    public function getJoinedDataSetInstanceId() {
        return $this->joinedDataSetInstanceId;
    }

    /**
     * @param int $joinedDataSetInstanceId
     */
    public function setJoinedDataSetInstanceId($joinedDataSetInstanceId) {
        $this->joinedDataSetInstanceId = $joinedDataSetInstanceId;
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

    /**
     * @return Field[]
     */
    public function getJoinColumns() {
        return $this->joinColumns;
    }

    /**
     * @param Field[] $joinColumns
     */
    public function setJoinColumns($joinColumns) {
        $this->joinColumns = $joinColumns;
    }


    public function getSQLTransformationProcessorKey() {
        return "join";
    }
}