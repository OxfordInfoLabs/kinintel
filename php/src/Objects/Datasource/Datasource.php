<?php

namespace Kinintel\Objects\Datasource;

use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Query\Query;

/**
 * Data source - these provide raw access to data using query objects
 *
 */
interface Datasource {

    /**
     * Apply a query to this data source and return a new (or the same) data source.
     * This is primarily designed to facilitate query chaining using a MultiQuery
     *
     * @param Query $query
     * @return Datasource
     */
    public function applyQuery($query);


    /**
     * Materialise this data source (after any queries have been applied)
     * and return a Dataset
     *
     * @return Dataset
     */
    public function materialise();


}