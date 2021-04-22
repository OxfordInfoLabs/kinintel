<?php

namespace Kinintel\Datasource;

use Kinintel\Dataset\Dataset;
use Kinintel\Query\Query;

/**
 * Data source - these provide raw access to data using query objects
 *
 */
interface Datasource {

    /**
     * Query a data source using a query object
     * and return a data set.
     *
     * @param Query $query
     * @return Dataset
     */
    public function query($query);


}