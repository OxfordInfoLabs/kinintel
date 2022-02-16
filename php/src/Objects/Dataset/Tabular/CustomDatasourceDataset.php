<?php


namespace Kinintel\Objects\Dataset\Tabular;

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
     * CustomDatasourceDataset constructor.
     *
     * @param SQLResultSetTabularDataset $sqlResultSetTabularDataset
     * @param string $instanceKey
     * @param string $instanceTitle
     */
    public function __construct($sqlResultSetTabularDataset, $instanceKey, $instanceTitle) {
        parent::__construct($sqlResultSetTabularDataset->returnResultSet(), $sqlResultSetTabularDataset->getColumns());
        $this->instanceKey = $instanceKey;
        $this->instanceTitle = $instanceTitle;
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


}