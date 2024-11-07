<?php

namespace Kinintel\Services\Util\SQLiteFunctions;

use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3CustomFunction;

class Reverse implements SQLite3CustomFunction {

    public function getName() {
        return "REVERSE";
    }

    public function execute(...$arguments) {
        $string = $arguments[0];
        return strrev($string);
    }
}