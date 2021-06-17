<?php


namespace Kinintel\Exception;


class DatasourceCompressionException extends \Exception {

    /**
     * Compression exception raised if an error occurs while decompressing a data source
     *
     * DatasourceCompressionException constructor.
     *
     * @param $message
     */
    public function __construct($message) {
        parent::__construct($message);
    }

}