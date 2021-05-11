<?php

namespace Kinintel\Exception;

use Throwable;

class DatasourceTransformationException extends \Exception {

    /**
     * Error thrown if an issue with a datasource transformation
     *
     * DatasourceTransformationException constructor.
     *
     * @param $message
     */
    public function __construct($message) {
        parent::__construct($message);
    }

}