<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Authentication\DefaultDatasourceCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;

/**
 * Default datasource which is used as fallback if a transformation cannot be fulfilled by
 * a given datasource (provided this datasource can handle it).
 *
 * Class DefaultDatasource
 * @package Kinintel\Objects\Datasource
 */
class DefaultDatasource extends SQLDatabaseDatasource {

    /**
     * @var Datasource|Dataset
     */
    private $sourceDataobject;

    /**
     * @var string
     */
    private $tableName;


    /**
     * @var boolean
     */
    private $populated = false;

    /**
     * @var boolean
     */
    private $empty = false;

    /**
     * Table index
     *
     * @var int
     */
    private static $tableIndex = 0;

    /**
     * @var SQLiteAuthenticationCredentials
     *
     */
    private static $credentials;

    /**
     * DefaultDatasource constructor.
     *
     * @param Datasource|Dataset $sourceDataobject
     */
    public function __construct($sourceDataobject) {
        $this->tableName = "table_" . ++self::$tableIndex;
        parent::__construct(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, $this->tableName, null),
            self::getCredentials(), new DatasourceUpdateConfig());
        $this->sourceDataobject = $sourceDataobject;

    }


    /**
     * Return the source datasource
     *
     * @return Datasource
     */
    public function returnSourceDatasource() {
        return $this->sourceDataobject;
    }


    /**
     * Populate this datasource
     *
     * @param array $parameterValues
     */
    public function populate($parameterValues = []) {

        if ($this->populated || $this->empty)
            return;

        if ($this->sourceDataobject instanceof Datasource) {
            // Firstly materialise the source datasource
            $sourceDataset = $this->sourceDataobject->materialise($parameterValues);
        } else
            $sourceDataset = $this->sourceDataobject;

        if (!$sourceDataset->getColumns()) {
            $this->empty = true;
            return;
        }

        // Convert columns to plain fields to avoid double evaluations
        $columns = Field::toPlainFields($sourceDataset->getColumns(), true);

        $this->updateFields($columns);

        // Update this data set with the source dataset.
        $this->update($sourceDataset);

        // Match columns in configuration with those from the source dataset
        $this->getConfig()->setColumns($columns);

        $this->populated = true;

    }


    /**
     * Materialise function overloaded to ensure that we create and populate the table first
     *
     * @param array $parameterValues
     * @return \Kinintel\Objects\Dataset\Dataset
     * @throws \Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException
     */
    public function materialise($parameterValues = []) {

        // Ensure population has occurred.x
        $this->populate($parameterValues);

        if ($this->empty) {
            return new ArrayTabularDataset([], []);
        }

        // Materialise this dataset
        return parent::materialise($parameterValues);

    }


    /**
     * Get the singleton credentials in use for this datasource
     *
     * @return SQLiteAuthenticationCredentials
     */
    public static function getCredentials() {

        if (!self::$credentials) {
            self::$credentials = new DefaultDatasourceCredentials();
        }

        return self::$credentials;
    }


}