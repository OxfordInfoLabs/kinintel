<?php

namespace Kinintel\Exception;

use Throwable;

class InvalidDatasourceQueryException extends \Exception {

    /**
     * Allow for custom message
     *
     * InvalidDatasourceQueryException constructor.
     *
     * @param $message
     */
    public function __construct($message) {
        if (!$message) {
            $message = "An invalid or missing query has been supplied to the datasource";
        }
        parent::__construct($message);
    }

}