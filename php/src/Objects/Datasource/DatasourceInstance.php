<?php


namespace Kinintel\Objects\Datasource;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Data source instance - can be stored in database table
 *
 * @table ki_datasource_instance
 * @generate
 */
class DatasourceInstance extends ActiveRecord {

    /**
     * @var string
     * @primaryKey
     */
    private $key;

    /**
     * Descriptive title for this data source instance
     *
     * @var string
     */
    private $title;

    /**
     * Type for this data source - can either be a mapping implementation key
     * or a fully qualified class path
     *
     * @var string
     */
    private $dataSourceType;

    /**
     * Config for the data source - should match the required format for
     * the configuration for the data source type.
     *
     * @var string
     * @json
     */
    private $dataSourceConfig;


    /**
     * Key to credentials instance if referencing a shared credentials object
     *
     * @var string
     */
    private $credentialsKey;


    /**
     * The type of credentials being referenced if not referencing by key.  Can be an implementation key or a
     * direct path to a fully qualified class.
     *
     * @var string
     */
    private $credentialsType;


    /**
     * Inline credentials config if not referencing instance by key.  Should be valid
     * config for the supplied type.
     *
     * @var string
     * @json
     */
    private $credentialsConfig;

    /**
     * DatasourceInstance constructor.
     *
     * @param string $key
     * @param string $title
     * @param string $dataSourceType
     * @param string $dataSourceConfig
     * @param string $credentialsKey
     * @param string $credentialsType
     * @param string $credentialsConfig
     */
    public function __construct($key, $title, $dataSourceType, $dataSourceConfig = [], $credentialsKey = null, $credentialsType = null, $credentialsConfig = []) {
        $this->key = $key;
        $this->title = $title;
        $this->dataSourceType = $dataSourceType;
        $this->dataSourceConfig = $dataSourceConfig;
        $this->credentialsKey = $credentialsKey;
        $this->credentialsType = $credentialsType;
        $this->credentialsConfig = $credentialsConfig;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDataSourceType() {
        return $this->dataSourceType;
    }

    /**
     * @param string $dataSourceType
     */
    public function setDataSourceType($dataSourceType) {
        $this->dataSourceType = $dataSourceType;
    }

    /**
     * @return string
     */
    public function getDataSourceConfig() {
        return $this->dataSourceConfig;
    }

    /**
     * @param string $dataSourceConfig
     */
    public function setDataSourceConfig($dataSourceConfig) {
        $this->dataSourceConfig = $dataSourceConfig;
    }

    /**
     * @return string
     */
    public function getCredentialsKey() {
        return $this->credentialsKey;
    }

    /**
     * @param string $credentialsKey
     */
    public function setCredentialsKey($credentialsKey) {
        $this->credentialsKey = $credentialsKey;
    }

    /**
     * @return string
     */
    public function getCredentialsType() {
        return $this->credentialsType;
    }

    /**
     * @param string $credentialsType
     */
    public function setCredentialsType($credentialsType) {
        $this->credentialsType = $credentialsType;
    }

    /**
     * @return string
     */
    public function getCredentialsConfig() {
        return $this->credentialsConfig;
    }

    /**
     * @param string $credentialsConfig
     */
    public function setCredentialsConfig($credentialsConfig) {
        $this->credentialsConfig = $credentialsConfig;
    }


}