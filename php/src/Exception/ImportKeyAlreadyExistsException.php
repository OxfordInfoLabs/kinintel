<?php


namespace Kinintel\Exception;

class ImportKeyAlreadyExistsException extends \Exception {

    public function __construct($importKey) {
        parent::__construct("The import key '$importKey' is already in use by one of your account datasources");
    }

}