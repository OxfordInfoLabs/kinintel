<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;

/**
 * Overload SQL Result Set dataset to include the instance key and title for datasource
 *
 * Class CustomDatasourceDataset
 * @package Kinintel\Objects\Dataset\Tabular
 */
class CustomDatasourceDataset extends SQLResultSetTabularDataset {

    /**
     * @var string
     */
    private $instanceKey;


    /**
     * @var string
     */
    private $instanceTitle;


    /**
     * @var string
     */
    private $instanceImportKey;


    /**
     * @var Index[]
     */
    private $indexes;

    /**
     * CustomDatasourceDataset constructor.
     *
     * @param SQLResultSetTabularDataset $sqlResultSetTabularDataset
     * @param string $instanceKey
     * @param string $instanceTitle
     * @param string $instanceImportKey
     * @param Index[] $indexes
     */
    public function __construct($sqlResultSetTabularDataset, $instanceKey, $instanceTitle, $instanceImportKey, $indexes = []) {
        parent::__construct($sqlResultSetTabularDataset->returnResultSet(), $sqlResultSetTabularDataset->getColumns());
        $this->instanceKey = $instanceKey;
        $this->instanceTitle = $instanceTitle;
        $this->instanceImportKey = $instanceImportKey;
        $this->indexes = $indexes;
    }

    /**
     * @return string
     */
    public function getInstanceKey() {
        return $this->instanceKey;
    }

    /**
     * @return string
     */
    public function getInstanceTitle() {
        return $this->instanceTitle;
    }

    /**
     * @return string
     */
    public function getInstanceImportKey() {
        return $this->instanceImportKey;
    }

    /**
     * @return Index[]
     */
    public function getIndexes(): array {
        return $this->indexes;
    }


}