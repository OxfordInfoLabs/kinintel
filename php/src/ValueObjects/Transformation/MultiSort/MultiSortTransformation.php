<?php


namespace Kinintel\ValueObjects\Transformation\MultiSort;


use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class MultiSortTransformation implements Transformation, SQLDatabaseTransformation {

    /**
     * @var Sort[]
     */
    private $sorts;

    /**
     * MultiSortTransformation constructor.
     *
     * @param Sort[] $sorts
     */
    public function __construct($sorts = []) {
        $this->sorts = $sorts;
    }


    /**
     * @return Sort[]
     */
    public function getSorts() {
        return $this->sorts;
    }

    /**
     * @param Sort[] $sorts
     */
    public function setSorts($sorts) {
        $this->sorts = $sorts;
    }


    public function getSQLTransformationProcessorKey() {
        return "multisort";
    }
}