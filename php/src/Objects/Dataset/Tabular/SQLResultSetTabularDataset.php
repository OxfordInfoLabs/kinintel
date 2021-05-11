<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinikit\Persistence\Database\ResultSet\ResultSet;
use Kinintel\ValueObjects\Dataset\Field;

class SQLResultSetTabularDataset extends TabularDataset {

    /**
     * Result set to use for next data item logic
     *
     * @var ResultSet
     */
    private $resultSet;


    /**
     * Only need a result set to construct this
     * as this contains all info we require.
     *
     * @param $resultSet
     */
    public function __construct($resultSet) {
        $this->resultSet = $resultSet;
    }

    /**
     * Get columns
     *
     * @return Field[]
     */
    public function getColumns() {
        return array_map(function ($columnName) {
            return new Field($columnName);
        }, $this->resultSet->getColumnNames());
    }


    /**
     * Simply read the next data item from the result set
     */
    public function nextDataItem() {
        return $this->resultSet->nextRow();
    }


}