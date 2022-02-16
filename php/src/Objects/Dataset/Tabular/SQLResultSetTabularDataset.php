<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinikit\Core\Logging\Logger;
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
    public function __construct($resultSet, $columns = []) {
        parent::__construct($columns);
        $this->resultSet = $resultSet;
    }

    /**
     * Return the underlying result set.
     *
     * @return ResultSet
     */
    public function returnResultSet() {
        return $this->resultSet;
    }


    /**
     * Get columns
     *
     * @return Field[]
     */
    public function getColumns() {

        if (!$this->columns || sizeof($this->columns) == 0) {
            $this->columns = array_map(function ($columnName) {
                return new Field($columnName);
            }, $this->resultSet->getColumnNames());
        }

        return parent::getColumns();
    }


    /**
     * Simply read the next data item from the result set
     */
    public function nextRawDataItem() {
        return $this->resultSet->nextRow();
    }


}