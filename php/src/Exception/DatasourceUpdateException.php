<?php


namespace Kinintel\Exception;


class DatasourceUpdateException extends \Exception {

    /**
     * Error thrown if an issue with a datasource update
     *
     * @param $message
     */
    public function __construct($message) {
        parent::__construct($message);
    }

}