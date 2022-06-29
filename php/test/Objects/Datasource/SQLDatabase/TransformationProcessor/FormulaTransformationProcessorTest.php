<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\BaseDatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Formula\Expression;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;

include_once "autoloader.php";

class FormulaTransformationProcessorTest extends \PHPUnit\Framework\TestCase {


    public function testUpdateQueryAppliesFormulaColumnsCorrectlyToQueryAndUpdatesDatasourceColumns() {

        $formulaTransformationProcessor = new FormulaTransformationProcessor();


        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $authenticationCredentials->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());

        $dataSource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test", null, [
            new Field("column1"), new Field("column2")
        ]),
            $authenticationCredentials, null);

        $transformation = new FormulaTransformation([
            new Expression("Computed", "[[column1]] + [[column2]]"),
            new Expression("Derived Column", "[[column3]] + 5 / [[column2]]"),
            new Expression("Parameter", "{{test}} + [[column1]]")]);


        $query = new SQLQuery("*", "sample_table");

        $query = $formulaTransformationProcessor->updateQuery($transformation, $query, ["test" => "Hello"], $dataSource);


        $this->assertEquals([
            new Field("column1"), new Field("column2"), new Field("computed", "Computed"),
            new Field("derivedColumn", "Derived Column"),
            new Field("parameter", "Parameter")
        ], $dataSource->getConfig()->getColumns());

        $this->assertEquals("SELECT *, \"column1\" + \"column2\" computed, \"column3\" + ? / \"column2\" derivedColumn, ? + \"column1\" parameter FROM sample_table",
            $query->getSQL());

        $this->assertEquals([5, "Hello"], $query->getParameters());

    }


    public function testIfFormulaReferencesPreviousCreatedFormulaThisIsSubstituted() {

        $formulaTransformationProcessor = new FormulaTransformationProcessor();


        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $authenticationCredentials->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());

        $dataSource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test", null, [
            new Field("column1"), new Field("column2")
        ]),
            $authenticationCredentials, null);

        $transformation = new FormulaTransformation([
            new Expression("Computed", "[[column1]] + [[column2]]"),
            new Expression("Derived Column", "[[column3]] + 5 / [[column2]]"),
            new Expression("Parameter", "{{test}} + [[column1]]")]);

        $dataSource->applyTransformation($transformation);


        $secondTransformation = new FormulaTransformation([
            new Expression("Second Level", "[[computed]] + [[parameter]]")
        ]);

        $dataSource->applyTransformation($secondTransformation);


        $query = new SQLQuery("*", "sample_table");

        $query = $formulaTransformationProcessor->updateQuery($transformation, $query, ["test" => "Hello"], $dataSource);


        $query = $formulaTransformationProcessor->updateQuery($secondTransformation, $query, ["test" => "Hello"], $dataSource);


        $this->assertEquals([
            new Field("column1"), new Field("column2"),
            new Field("computed", "Computed"),
            new Field("derivedColumn", "Derived Column"),
            new Field("parameter", "Parameter"),
            new Field("secondLevel", "Second Level")
        ], $dataSource->getConfig()->getColumns());

        $this->assertEquals("SELECT *, \"column1\" + \"column2\" computed, \"column3\" + ? / \"column2\" derivedColumn, ? + \"column1\" parameter, (\"column1\" + \"column2\") + (? + \"column1\") secondLevel FROM sample_table",
            $query->getSQL());

        $this->assertEquals([5, "Hello", "Hello"], $query->getParameters());

    }


}