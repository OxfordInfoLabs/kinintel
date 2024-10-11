<?php


namespace Kinintel\Exception;

class ManagementKeyAlreadyExistsException extends \Exception {

    public function __construct($managementKey) {
        parent::__construct("The management key '$managementKey' is already in use by one of your account datasets");
    }

}