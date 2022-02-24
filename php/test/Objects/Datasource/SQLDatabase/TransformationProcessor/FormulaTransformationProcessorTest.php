<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Formula\Expression;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;

include_once "autoloader.php";

class FormulaTransformationProcessorTest extends \PHPUnit\Framework\TestCase {


    public function testUpdateQueryAppliesFormulaColumnsCorrectlyToQueryAndUpdatesDatasourceColumns() {

        $formulaTransformationProcessor = new FormulaTransformationProcessor();

        $dataSource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test", null, [
            new Field("column1"), new Field("column2")
        ]),
            null, null);

        $transformation = new FormulaTransformation([
            new Expression("Computed", "[[column1]] + [[column2]]"),
            new Expression("Derived Column", "[[column3]] + 5 / [[column2]]")]);


        $query = new SQLQuery("*", "sample_table");

        $query = $formulaTransformationProcessor->updateQuery($transformation, $query, [], $dataSource);


        $this->assertEquals([
            new Field("column1"), new Field("column2"), new Field("computed", "Computed"),
            new Field("derivedColumn", "Derived Column")
        ], $dataSource->getConfig()->getColumns());

        $this->assertEquals("SELECT * FROM (SELECT *, column1 + column2 computed, column3 + ? / column2 derivedColumn FROM sample_table) F1",
            $query->getSQL());

        $this->assertEquals([5], $query->getParameters());

    }
}