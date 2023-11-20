<?php

namespace Kinintel\Services\Util\SQLiteFunctions;

use Kinikit\Core\Util\MathsUtils;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3CustomFunction;

class Dot implements SQLite3CustomFunction {

    public function getName() {
        return "DOT_PRODUCT";
    }


    /**
     * Execute and return the dot product
     *
     * @param mixed ...$arguments
     * @return int|mixed
     */
    public function execute(...$arguments) {
        return MathsUtils::dot($arguments[0], $arguments[1]);
    }
}
