<?php


namespace Kinintel\Exception;


class ItemInUseException extends \Exception {

    public function __construct($datasourceInstance) {
        parent::__construct("The item {$datasourceInstance->getTitle()} cannot be deleted as it is referenced by other items in the system.");
    }

}