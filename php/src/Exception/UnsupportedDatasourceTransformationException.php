<?php


namespace Kinintel\Exception;


class UnsupportedDatasourceTransformationException extends \Exception {

    public function __construct($datasource, $transformation) {
        parent::__construct("The datasource of type " . get_class($datasource) . " does not support the transformation of type " . get_class($transformation));
    }

}