<?php


namespace Kinintel\Exception;


class DatasourceUpdateException extends \Exception {

    /**
     * Error thrown if an issue with a datasource update
     *
     * DatasourceTransformationException constructor.
     *
     * @param $message
     */
    public function __construct($message) {
        parent::__construct($message);
    }

}