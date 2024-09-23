<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Columns\ColumnNamingConvention;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class ColumnsTransformationProcessor extends SQLTransformationProcessor {

    /**
     * The table's alias number e.g. for use in `C1.tableColumnName as table_column_name`.
     * We need to keep incrementing this between transformations so we aren't using the same
     * table name in two different columns transformations in the same query.
     *
     * @var int
     */
    private int $aliasIndex = 0;


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
     * @param ColumnsTransformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param SQLDatabaseDatasource $dataSource
     * @return SQLQuery
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {

        $dataSourceConfig = $dataSource->getConfig();
        $resetColumnNames = $transformation->isResetColumnNames();

        $newColumns = [];
        if (is_array($dataSourceConfig->getColumns())) {
            $existingColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $dataSourceConfig->getColumns());

            // Handle alias logic if resetting columns
            $aliasStrings = [];
            $newColumnTitles = [];
            if ($resetColumnNames) $this->aliasIndex++;

            foreach ($transformation->getColumns() as $newColumn) {
                $existingColumn = $existingColumns[$newColumn->getName()] ?? null;
                if ($existingColumn) {

                    // If resetting column names, create alias strings
                    if ($resetColumnNames) {

                        // Track number of occurrences of titles for suffix management
                        $suffix = "";
                        if (!isset($newColumnTitles[$newColumn->getTitle()]))
                            $newColumnTitles[$newColumn->getTitle()] = 1;
                        else
                            $suffix = " " . ++$newColumnTitles[$newColumn->getTitle()];

                        $newColumnName = $transformation->getNamingConvention() == ColumnNamingConvention::UNDERSCORE ?
                            StringUtils::convertToSnakeCase($newColumn->getTitle() . $suffix, true) : StringUtils::convertToCamelCase($newColumn->getTitle() . $suffix, true);
                        $aliasStrings[] = "C" . $this->aliasIndex . "." . $newColumn->getName() . " AS " . $newColumnName;
                    } else {
                        $newColumnName = $newColumn->getName();
                    }

                    $newColumns[] = new Field($newColumnName, $newColumn->getTitle(), null,
                        $existingColumn->getType(), $existingColumn->isKeyField());
                } else {
                    $newColumns[] = $newColumn;
                }
            }
        } else {
            $newColumns = $transformation->getColumns();
        }


        $dataSourceConfig->setColumns($newColumns);

        // Reset the query if required
        if ($resetColumnNames) {
            $query = new SQLQuery(join(", ", $aliasStrings), "(" . $query->getSQL() . ") C" . $this->aliasIndex);
        }

        return $query;
    }
}
