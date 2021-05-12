<?php


namespace Kinintel\Exception;


class DatasourceMaterialiseException extends \Exception {

    /**
     * Materialise exception raised if an error occurs while materialising a data source
     *
     * DatasourceTransformationException constructor.
     *
     * @param $message
     */
    public function __construct($message) {
        parent::__construct($message);
    }

}