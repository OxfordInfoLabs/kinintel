<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Formula\Expression;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseExpression;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;

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


    public function testIfSummarisationTransformationPrecedesFormulaTransformationQueryIsWrappedAccordingly() {

        $formulaTransformationProcessor = new FormulaTransformationProcessor();


        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $authenticationCredentials->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());

        $dataSource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test", null, [
            new Field("column1"), new Field("column2")
        ]),
            $authenticationCredentials, null);

        // Apply a summarisation first
        $summarisation = new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, "bingo", "MAX([[column1]])")]);
        $dataSource->applyTransformation($summarisation);

        $query = new SQLQuery("MAX(column1) bingo", "test");

        $formulaTransformation = new FormulaTransformation([new Expression("test", "[[bingo]]")]);

        $dataSource->applyTransformation($formulaTransformation);

        $query = $formulaTransformationProcessor->updateQuery($formulaTransformation, $query, [], $dataSource);

        $this->assertEquals('SELECT F1.*, "bingo" test FROM (SELECT MAX(column1) bingo FROM test) F1', $query->getSQL());


    }

    public function testReturnAlteredColumnsReturnsSameAsAMaterialisedDataset(){

        /** @var SQLite3DatabaseConnection $dbConnection */
        $dbConnection = Container::instance()
            ->get(AuthenticationCredentialsService::class)
            ->getCredentialsInstanceByKey("test")
            ->returnCredentials()
            ->returnDatabaseConnection();

        $dbConnection->executeScript(<<<SQL
DROP TABLE IF EXISTS __formulaTest;
CREATE TABLE IF NOT EXISTS __formulaTest (
name VARCHAR(255),
age INT,
PRIMARY KEY (name)
);
SQL
);
        $DSI = new DatasourceInstance(
            "formula_test",
            "Testing",
            "sqldatabase",
            new SQLDatabaseDatasourceConfig(
                SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                "__formulaTest"
            ),
            "test"
        );

        $formulaTransformation = new FormulaTransformation(
            [
                new Expression("Description", "CONCAT([[name]], ', ', [[age]])")
            ]
        );

        /** @var SQLDatabaseDatasource $out */
        $out = $DSI->returnDataSource()->applyTransformation($formulaTransformation);
        /** @var ArrayTabularDataset $results */
        $results = $out->materialise();
        $this->assertCount(3, $results->getColumns());
        $expectedColumns = [
            new Field("name"),
            new Field("age", type: Field::TYPE_INTEGER),
            new Field("description"),
        ];
        $this->assertEquals($expectedColumns, $results->getColumns());
        $returnedFields = $out->returnFields([], true);
        foreach ($returnedFields as $returnedField){ // Ignore key field status
            $returnedField->setKeyField(false);
            $returnedField->setRequired(false);
        }
        $this->assertEquals($expectedColumns, $returnedFields);

    }


}