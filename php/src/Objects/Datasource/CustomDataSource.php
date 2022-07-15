<?php


namespace Kinintel\Objects\Datasource;


use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dataset\Tabular\CustomDatasourceDataset;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;

/**
 * Simple extension of the SQL data source to return a custom datasource dataset
 *
 * Class CustomDataSource
 * @package Kinintel\Objects\Datasource
 */
class CustomDataSource extends SQLDatabaseDatasource {

    /**
     * Overload Materialise to return subclassed Dataset
     *
     * @param array $parameterValues
     * @return CustomDatasourceDataset
     */
    public function materialiseDataset($parameterValues = []) {
        $sqlResultSet = parent::materialiseDataset($parameterValues);
        return new CustomDatasourceDataset($sqlResultSet, $this->getInstanceInfo()->getKey(), $this->getInstanceInfo()->getTitle());
    }


}
