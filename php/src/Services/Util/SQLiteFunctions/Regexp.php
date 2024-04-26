<?php

namespace Kinintel\Services\Util\SQLiteFunctions;

use Kinikit\Core\Logging\Logger;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3CustomFunction;

class Regexp implements SQLite3CustomFunction {

    public function getName() {
        return "REGEXP";
    }


    /**
     * Execute and return a like based on a regexp rhs
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    public function execute(...$arguments) : int {
        return (sizeof($arguments) == 2) && preg_match('/^' . $arguments[0] . '$/i', $arguments[1]) ? 1 : 0;
    }

}
