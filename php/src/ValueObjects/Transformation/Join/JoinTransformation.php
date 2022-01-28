<?php


namespace Kinintel\ValueObjects\Transformation\Join;


use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
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
    private $joinParameterMappings = [];


    /**
     * Join filters for filtering a joined data set referencing
     * columns on both sides of a join.
     *
     * @var FilterJunction
     */
    private $joinFilters;


    /**
     * Strict join
     *
     * @var bool
     */
    private $strictJoin = false;


    /**
     * Which columns are to be included - here is also an opportunity to rename them.
     *
     * @var Field[]
     */
    private $joinColumns = [];


    /**
     * Evaluated data source in use for the joined data
     *
     * @var Datasource
     */
    private $evaluatedDataSource = null;


    /**
     * JoinTransformation constructor.
     *
     * @param string $joinedDataSourceKey
     * @param int $joinedDataSetId
     * @param JoinParameterMapping[] $joinParameterMappings
     * @param FilterJunction $joinFilters
     * @param Field[] $joinColumns
     */
    public function __construct($joinedDataSourceKey = null, $joinedDataSetId = null, $joinParameterMappings = [],
                                $joinFilters = null, $joinColumns = [], $strictJoin = false) {
        $this->joinedDataSourceInstanceKey = $joinedDataSourceKey;
        $this->joinedDataSetInstanceId = $joinedDataSetId;
        $this->joinParameterMappings = $joinParameterMappings;
        $this->joinFilters = $joinFilters;
        $this->joinColumns = $joinColumns;
        $this->strictJoin = $strictJoin;
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
     * Get the joined data item title
     *
     * @return string
     */
    public function getJoinedDataItemTitle(){
        if ($this->joinedDataSourceInstanceKey) {
            $datasourceService = Container::instance()->get(DatasourceService::class);
            $datasource = $datasourceService->getDataSourceInstanceByKey($this->joinedDataSourceInstanceKey);
            return $datasource->getTitle();
        } else if ($this->getJoinedDataSetInstanceId()) {
            $datasetService = Container::instance()->get(DatasetService::class);
            $dataset = $datasetService->getDataSetInstance($this->joinedDataSetInstanceId);
            return $dataset->getTitle();
        }
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
     * @return bool
     */
    public function isStrictJoin() {
        return $this->strictJoin;
    }

    /**
     * @param bool $strictJoin
     */
    public function setStrictJoin($strictJoin) {
        $this->strictJoin = $strictJoin;
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

    /**
     * Set an evaluated data source (for convenience in processing)
     *
     * @param Datasource $evaluatedDataSource
     */
    public function setEvaluatedDataSource($evaluatedDataSource) {
        $this->evaluatedDataSource = $evaluatedDataSource;
    }


    /**
     * Return the evaluated data source
     *
     * @return Datasource
     */
    public function returnEvaluatedDataSource() {
        return $this->evaluatedDataSource;
    }


    /**
     * @return string
     */
    public function getSQLTransformationProcessorKey() {
        return "join";
    }
}