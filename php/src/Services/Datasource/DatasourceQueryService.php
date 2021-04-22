<?php

namespace Kinintel\Services\Datasource;

use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\ValueObjects\Query\Query;

/**
 * Data source query service - for managing queries to data sources
 *
 * Class DatasourceService
 */
class DatasourceQueryService {

    /**
     * Query a data source and return a data set
     *
     * @param Datasource $dataSource
     * @param Query $query
     *
     * @return Dataset
     */
    public function queryDataSource($dataSource, $query) {

        // Apply the query to the data source
        $queriedSource = $dataSource->applyQuery($query);

        // Return the materialised dataset.
        return $queriedSource->materialise();

    }

}