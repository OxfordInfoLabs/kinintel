<?php


namespace Kinintel\Services\Util\SQLiteFunctions;


use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3CustomFunction;

class Levenshtein implements SQLite3CustomFunction {

    public function getName() {
        return "LEVENSHTEIN";
    }


    /**
     * Execute and return the levenshtein distance
     *
     * @param mixed ...$arguments
     * @return int|mixed
     */
    public function execute(...$arguments) {
        return levenshtein($arguments[0], $arguments[1]);
    }
}
