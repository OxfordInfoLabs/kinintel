<?php


namespace Kinintel\ValueObjects\Query;

use Kinintel\ValueObjects\Query\Filter\Sort;

/**
 * Simple filter query
 *
 * @package Kinintel\Query
 */
class FilterQuery implements Query {

    /**
     * An array of Filter / FilterJunction objects
     *
     * @var mixed[]
     */
    private $filterObjects;


    /**
     * @var Sort[]
     */
    private $sorts;


    /**
     * @var int
     */
    private $offset;


    /**
     * @var int
     */
    private $limit;

    /**
     * FilterQuery constructor.
     *
     * @param mixed[] $filterObjects
     * @param Sort[] $sorts
     * @param int $offset
     * @param int $limit
     */
    public function __construct($filterObjects = [], $sorts = [], $offset = null, $limit = null) {
        $this->filterObjects = $filterObjects;
        $this->sorts = $sorts;
        $this->offset = $offset;
        $this->limit = $limit;
    }


    /**
     * @return mixed[]
     */
    public function getFilterObjects() {
        return $this->filterObjects;
    }

    /**
     * @return Sort[]
     */
    public function getSorts() {
        return $this->sorts;
    }

    /**
     * @return int
     */
    public function getOffset() {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }


}