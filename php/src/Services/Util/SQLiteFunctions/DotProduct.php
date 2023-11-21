<?php

namespace Kinintel\Services\Util\SQLiteFunctions;

use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\MathsUtils;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3CustomFunction;

class DotProduct implements SQLite3CustomFunction {

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
        Logger::log($arguments);
        $args = [json_decode($arguments[0]), json_decode($arguments[1])];
        if (!is_array($args[0]) || !is_array($args[1])) return null;
        return MathsUtils::dot(...$args);
    }
}
