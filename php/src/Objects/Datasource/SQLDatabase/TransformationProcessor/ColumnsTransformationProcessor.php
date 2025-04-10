<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
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


        /** @var SQLDatabaseDatasourceConfig $dataSourceConfig */
        $dataSourceConfig = $dataSource->getConfig();
        $resetColumnNames = $transformation->isResetColumnNames();


        if (is_array($dataSourceConfig->getColumns())) {

            // It's possible that deriving up to transformation could cause a bug if we have an identical transformation twice.
            $existingColumns = $dataSource->returnFields($parameterValues, $transformation);
            $existingColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $existingColumns);

            // Handle alias logic if resetting columns
            if ($resetColumnNames) $this->aliasIndex++;
        }


        // Returns the altered transformations
        $newColumns = $transformation->returnAlteredColumns($existingColumns);

        $aliasStrings = [];
        if (array_keys($newColumns) != array_keys($transformation->getColumns())) {
            throw new \Exception("Assertion Failed! Altered Columns should have the same keys as transformation columns");
        }

        for ($i = 0; $i < count($newColumns); $i++) { // I wish there were zip
            $aliasStrings[] = "C" . $this->aliasIndex . "." . $transformation->getColumns()[$i]->getName() . " AS " . $newColumns[$i]->getName();
        }


        // Update the logic to update columns.
        $dataSourceConfig->setColumns($newColumns);

        // Reset the query if required
        if ($resetColumnNames) {
            if (!isset($aliasStrings) || !$aliasStrings) throw new \Exception("NO ALIAS STRINGS");
            $query = new SQLQuery(join(", ", $aliasStrings), "(" . $query->getSQL() . ") C" . $this->aliasIndex, $query->getParameters());
        }

        return $query;
    }
}
