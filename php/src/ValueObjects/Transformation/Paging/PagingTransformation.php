<?php


namespace Kinintel\ValueObjects\Transformation\Paging;


use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class PagingTransformation implements Transformation, SQLDatabaseTransformation {

    /**
     * @var integer
     */
    private $limit;

    /**
     * @var integer
     */
    private $offset;


    /**
     * Applied flag to indicate that paging has been applied.
     *
     * @var bool
     */
    private $applied = false;

    /**
     * PagingTransformation constructor.
     * @param int $limit
     * @param int $offset
     */
    public function __construct($limit = null, $offset = null) {
        $this->limit = $limit;
        $this->offset = $offset;
    }


    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit) {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getOffset() {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset) {
        $this->offset = $offset;
    }

    /**
     * @return bool
     */
    public function isApplied() {
        return $this->applied;
    }

    /**
     * @param bool $applied
     */
    public function setApplied($applied) {
        $this->applied = $applied;
    }


    public function getSQLTransformationProcessorKey() {
        return "paging";
    }
}