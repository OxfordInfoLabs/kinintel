<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class ColumnsTransformationProcessor extends SQLTransformationProcessor {

    /**
     * Table alias index
     *
     * @var int
     */
    private $tableIndex = 0;

    /**
     * Apply the transformation to the passed datasource
     *
     * @param ColumnsTransformation $transformation
     * @param SQLDatabaseDatasource $datasource
     * @param mixed[] $parameterValues
     * @param null $pagingTransformation
     * @return \Kinintel\Objects\Datasource\Datasource|void
     */
    public function applyTransformation($transformation, $datasource, $parameterValues = [], $pagingTransformation = null) {
        return $datasource;
    }


    /**
     * Leave query intact
     *
     * @param Transformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param SQLDatabaseDatasource $dataSource
     * @return SQLQuery
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {

        $dataSourceConfig = $dataSource->getConfig();

        $newColumns = [];
        $columnNames = [];
        $tableAlias = "C" . ++$this->tableIndex;
        $wrap = $query->getSelectClause() != "*";

        if (is_array($dataSourceConfig->getColumns())) {
            $existingColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $dataSourceConfig->getColumns());
            foreach ($transformation->getColumns() as $newColumn) {
                $existingColumn = $existingColumns[$newColumn->getName()] ?? null;
                if ($existingColumn) {
                    $newColumns[] = new Field($existingColumn->getName(), $newColumn->getTitle(), null,
                        $existingColumn->getType(), $existingColumn->isKeyField());
                } else {
                    $newColumns[] = $newColumn;
                }
                $columnNames[] = $wrap ? "$tableAlias.{$newColumn->getName()}" : $newColumn->getName();
            }
        } else {
            $newColumns = $transformation->getColumns();
        }

        $dataSourceConfig->setColumns($newColumns);

        $selectClause = implode(",", $columnNames);

        if ($wrap) {
            $SQLQuery = new SQLQuery($selectClause, "({$query->getSQL()}) $tableAlias", $query->getParameters());
            return $SQLQuery;
        } else {
            $query->setSelectClause($selectClause);
            return $query;
        }
    }
}