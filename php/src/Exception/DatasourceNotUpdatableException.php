<?php


namespace Kinintel\Exception;


class DatasourceNotUpdatableException extends \Exception {

    public function __construct($datasource) {
        parent::__construct("The datasource of type " . get_class($datasource) . " is not updatable");
    }

}