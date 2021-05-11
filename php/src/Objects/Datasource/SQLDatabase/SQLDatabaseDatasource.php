<?php


namespace Kinintel\Objects\Datasource\SQLDatabase;


use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Transformation;

class SQLDatabaseDatasource extends Datasource {


    /**
     * Return the config class for this datasource
     *
     * @return string
     */
    public function getConfigClass() {
        return SQLDatabaseDatasourceConfig::class;
    }

    /**
     * Return array of credential classes supported by this data source
     *
     * @return string[]
     */
    public function getSupportedCredentialClasses() {
        return [
            SQLiteAuthenticationCredentials::class,
            MySQLAuthenticationCredentials::class
        ];
    }

    /**
     * Always require authentication for SQL Database data sources
     *
     * @return bool
     */
    public function isAuthenticationRequired() {
        return true;
    }


    /**
     * Apply a transformation to the SQL database
     *
     * @param Transformation $transformation
     * @return Datasource|void
     */
    public function applyTransformation($transformation) {

    }


    /**
     * Materialise this dataset using a SQL query
     *
     * @return Dataset|void
     */
    public function materialiseDataset() {

    }
}