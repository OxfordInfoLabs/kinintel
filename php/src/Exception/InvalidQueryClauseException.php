<?php

namespace Kinintel\Exception;

class InvalidQueryClauseException extends \Exception {

    public function __construct($clause) {
        parent::__construct("Invalid clause supplied for query: " . $clause);
    }

}