<?php


namespace Kinintel\Exception;


use Kinikit\Core\Exception\ItemNotFoundException;

class FeedNotFoundException extends ItemNotFoundException {

    public function __construct($path) {
        parent::__construct("The feed with path '$path' does not exist");
    }

}