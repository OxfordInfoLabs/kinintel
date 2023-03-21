<?php


namespace Kinintel\Exception;


use Kinikit\Core\Exception\ItemNotFoundException;

class ExternalDashboardNotFoundException extends ItemNotFoundException {

    public function __construct($id) {
        parent::__construct("The external dashboard with id '$id' does not exist");
    }

}