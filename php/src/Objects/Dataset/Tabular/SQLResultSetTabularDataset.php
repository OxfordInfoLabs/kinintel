<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\Database\ResultSet\ResultSet;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLColumnFieldMapper;
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

        $sqlColumnFieldMapper = new SQLColumnFieldMapper();

        if (!$this->columns || sizeof($this->columns) == 0) {
            $this->columns = array_map(function ($column) use ($sqlColumnFieldMapper) {
                return $sqlColumnFieldMapper->mapResultSetColumnToField($column);
            }, $this->resultSet->getColumns());
        } else {
            // Get result set columns
            $resultSetColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $this->resultSet->getColumns());
            foreach ($this->columns as $column) {
                if (isset($resultSetColumns[$column->getName()])) {
                    $mappedType = $sqlColumnFieldMapper->mapResultSetColumnToField($resultSetColumns[$column->getName()]);

                    // Don't remap id types
                    if (($column->getType() !== Field::TYPE_ID) && ($column->getType() !== Field::TYPE_PICK_FROM_SOURCE))
                        $column->setType($mappedType->getType());
                }
            }
        }


        return parent::getColumns();
    }


    /**
     * Simply read the next data item from the result set
     */
    public function nextRawDataItem() {
        return $this->resultSet->nextRow() ?? false;
    }


}
