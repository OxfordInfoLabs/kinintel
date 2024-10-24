<?php

namespace Kinintel\Test\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kiniauth\Services\Security\AuthenticationService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\ColumnsTransformationProcessor;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SQLTransformationProcessor;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Columns\ColumnNamingConvention;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class ColumnsTransformationProcessorTest extends TestCase {



    private ColumnsTransformationProcessor $processor;


    public function setUp(): void {
        $this->processor = new ColumnsTransformationProcessor();
    }


    public function testColumnsTransformationWithoutResetPreservesQueryAndPreviousNamingOfColumns() {

        $transformation = new ColumnsTransformation([
            new Field("column1", "Updated Column 1"),
            new Field("column3", "Updated Column 3")
        ]);

        $query = new SQLQuery("*", "test");
        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = new SQLDatabaseDatasourceConfig("table", "test", "", [
            new Field("column1", "Column 1"),
            new Field("column2", "Column 2"),
            new Field("column3", "Column 3"),
            new Field("column4", "Column 4")
        ]);
        $datasource->returnValue("getConfig", $datasourceConfig);

        $updatedQuery = $this->processor->updateQuery($transformation, $query, [], $datasource);

        // Check query unaffected
        $this->assertEquals($query, $updatedQuery);

        // Check columns have changed
        $this->assertEquals([new Field("column1", "Updated Column 1"),
            new Field("column3", "Updated Column 3")], $datasourceConfig->getColumns());

    }

    public function testIfResetWithCamelCaseSuppliedQueryIsWrappedWithExplicitIdentifiersAndFieldsUpdated() {

        $transformation = new ColumnsTransformation([
            new Field("column1", "Updated Column 1"),
            new Field("column3", "Updated Column 3")
        ], true, ColumnNamingConvention::CAMEL);

        $query = new SQLQuery("*", "test");
        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = new SQLDatabaseDatasourceConfig("table", "test", "", [
            new Field("column1", "Column 1"),
            new Field("column2", "Column 2"),
            new Field("column3", "Column 3"),
            new Field("column4", "Column 4")
        ]);
        $datasource->returnValue("getConfig", $datasourceConfig);

        $updatedQuery = $this->processor->updateQuery($transformation, $query, [], $datasource);

        // Check query unaffected
        $expectedQuery = new SQLQuery("C1.column1 AS updatedColumn1, C1.column3 AS updatedColumn3",
            "(SELECT * FROM test) C1");

        $this->assertEquals($expectedQuery, $updatedQuery);

        // Check columns have changed
        $this->assertEquals([new Field("updatedColumn1", "Updated Column 1"),
            new Field("updatedColumn3", "Updated Column 3")], $datasourceConfig->getColumns());


    }


    public function testIfResetWithUnderscoreSuppliedQueryIsWrappedWithExplicitIdentifiersAndFieldsUpdated() {

        $transformation = new ColumnsTransformation([
            new Field("column1", "Updated Column 1"),
            new Field("column3", "Updated Column 3")
        ], true, ColumnNamingConvention::UNDERSCORE);

        $query = new SQLQuery("*", "test");
        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = new SQLDatabaseDatasourceConfig("table", "test", "", [
            new Field("column1", "Column 1"),
            new Field("column2", "Column 2"),
            new Field("column3", "Column 3"),
            new Field("column4", "Column 4")
        ]);
        $datasource->returnValue("getConfig", $datasourceConfig);

        $updatedQuery = $this->processor->updateQuery($transformation, $query, [], $datasource);

        // Check query unaffected
        $expectedQuery = new SQLQuery("C1.column1 AS updated_column_1, C1.column3 AS updated_column_3",
            "(SELECT * FROM test) C1");

        $this->assertEquals($expectedQuery, $updatedQuery);

        // Check columns have changed
        $this->assertEquals([new Field("updated_column_1", "Updated Column 1"),
            new Field("updated_column_3", "Updated Column 3")], $datasourceConfig->getColumns());

    }

    public function testIfMultipleColumnTransformationsAreChainedTheyCorrectlyAlias() {


        $transformation = new ColumnsTransformation([
            new Field("column1", "Updated Column 1"),
            new Field("column3", "Updated Column 3")
        ], true, ColumnNamingConvention::UNDERSCORE);

        $query = new SQLQuery("*", "test");
        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = new SQLDatabaseDatasourceConfig("table", "test", "", [
            new Field("column1", "Column 1"),
            new Field("column2", "Column 2"),
            new Field("column3", "Column 3"),
            new Field("column4", "Column 4")
        ]);
        $datasource->returnValue("getConfig", $datasourceConfig);

        $updatedQuery = $this->processor->updateQuery($transformation, $query, [], $datasource);

        $transformation2 = new ColumnsTransformation([
            new Field("updated_column_1", "Reset Column 1"),
            new Field("updated_column_3", "Other Reset Column 2"),
        ], true, ColumnNamingConvention::CAMEL);

        $updatedQuery = $this->processor->updateQuery($transformation2, $updatedQuery, [], $datasource);


        // Check query unaffected
        $expectedQuery = new SQLQuery("C2.updated_column_1 AS resetColumn1, C2.updated_column_3 AS otherResetColumn2",
            "(SELECT C1.column1 AS updated_column_1, C1.column3 AS updated_column_3 FROM (SELECT * FROM test) C1) C2");

        $this->assertEquals($expectedQuery, $updatedQuery);

        // Check columns have changed
        $this->assertEquals([new Field("resetColumn1", "Reset Column 1"),
            new Field("otherResetColumn2", "Other Reset Column 2")], $datasourceConfig->getColumns());

    }


    public function testIfDuplicateColumnTitlesSuppliedNumberAutoAppendedToColumnName() {

        $transformation = new ColumnsTransformation([
            new Field("column1", "Updated Column"),
            new Field("column3", "Updated Column"),
            new Field("column4", "Updated Column")
        ], true, ColumnNamingConvention::UNDERSCORE);

        $query = new SQLQuery("*", "test");
        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = new SQLDatabaseDatasourceConfig("table", "test", "", [
            new Field("column1", "Column 1"),
            new Field("column2", "Column 2"),
            new Field("column3", "Column 3"),
            new Field("column4", "Column 4")
        ]);
        $datasource->returnValue("getConfig", $datasourceConfig);

        $updatedQuery = $this->processor->updateQuery($transformation, $query, [], $datasource);

        // Check query unaffected
        $expectedQuery = new SQLQuery("C1.column1 AS updated_column, C1.column3 AS updated_column_2, C1.column4 AS updated_column_3",
            "(SELECT * FROM test) C1");

        $this->assertEquals($expectedQuery, $updatedQuery);

        // Check columns have changed
        $this->assertEquals([new Field("updated_column", "Updated Column"),
            new Field("updated_column_2", "Updated Column"),
            new Field("updated_column_3", "Updated Column")], $datasourceConfig->getColumns());

    }

    public function testCanPerformColumnsTransformationOnDatasourceWithoutExplicitColumns() {
        /** @var SQLDatabaseCredentials $creds */
        $creds = Container::instance()
            ->get(AuthenticationCredentialsService::class)
            ->getCredentialsInstanceByKey("test")
            ->returnCredentials();
        $dbConnection = $creds->returnDatabaseConnection();
        $dbConnection->executeScript("
        DROP TABLE IF EXISTS __columnsTransformationTest;
        CREATE TABLE __columnsTransformationTest (
            name VARCHAR(255) NOT NULL,
            age INTEGER NOT NULL,
            PRIMARY KEY (name)
        );
        INSERT INTO __columnsTransformationTest (name, age) VALUES 
        ('Maurice', 22);
        ");

        $datasourceConfig = new SQLDatabaseDatasourceConfig(
            SQLDatabaseDatasourceConfig::SOURCE_TABLE ,
            "__columnsTransformationTest"
        );

        $datasource = new SQLDatabaseDatasource($datasourceConfig, $creds, new DatasourceUpdateConfig(["name", "age"]), instanceKey: "columns-transformation-test");
        $transformation = new ColumnsTransformation(
            [new Field("name", "Name")],
            true
        );
        $fields = $datasource->returnFields([]);
        $this->assertEquals($fields, [new Field("name", keyField: true), new Field("age", type: Field::TYPE_INTEGER)]);
        $out = $datasource->applyTransformation($transformation);
        $this->assertEquals($out, $datasource);
        $fields = $datasource->returnFields([]);
        $this->assertEquals([new Field("name")], $fields);
        $actualDataset = $out->materialise();
        $actual = $actualDataset->getAllData();

        $this->assertEquals([["name" => "Maurice"]], $actual);
    }

}