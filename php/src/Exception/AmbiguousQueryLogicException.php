<?php

namespace Kinintel\Exception;

class AmbiguousQueryLogicException extends \Exception {

    public function __construct($clause) {
        parent::__construct("Ambiguous query logic supplied in query - please use brackets when mixing AND/OR expressions: " . $clause);
    }

}