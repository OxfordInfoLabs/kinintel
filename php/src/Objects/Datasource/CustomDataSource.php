<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\Objects\Dataset\Tabular\CustomDatasourceDataset;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfigConfig;

/**
 * Simple extension of the SQL data source to return a custom datasource dataset
 *
 * Class CustomDataSource
 * @package Kinintel\Objects\Datasource
 */
class CustomDataSource extends SQLDatabaseDatasource {

    /**
     * Get the config class for the custom data source
     *
     * @return string
     */
    public function getConfigClass() {
        return ManagedTableSQLDatabaseDatasourceConfigConfig::class;
    }


    /**
     * Overload Materialise to return subclassed Dataset
     *
     * @param array $parameterValues
     * @return CustomDatasourceDataset
     */
    public function materialiseDataset($parameterValues = []) {
        $sqlResultSet = parent::materialiseDataset($parameterValues);
        return new CustomDatasourceDataset($sqlResultSet, $this->getInstanceInfo()->getKey(), $this->getInstanceInfo()->getTitle(), $this->getInstanceInfo()->getImportKey());
    }


}
