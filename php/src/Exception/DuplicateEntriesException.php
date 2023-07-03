<?php

namespace Kinintel\Exception;


class DuplicateEntriesException extends \Exception {

    public function __construct() {
        parent::__construct("Some of the entries supplied duplicate existing entries with the same unique identifier");
    }
}