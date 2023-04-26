<?php

namespace Kinintel\Test\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\ColumnsTransformationProcessor;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;

include_once "autoloader.php";

class ColumnsTransformationProcessorTest extends TestBase {

    /**
     * @var ColumnsTransformationProcessor
     */
    private $columnsTransformationProcessor;

    public function setUp(): void {
        $this->columnsTransformationProcessor = new ColumnsTransformationProcessor();
    }


    public function testCanReduceColumnsCorrectly() {

        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $datasource->returnValue("getConfig", $datasourceConfig);
        $datasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $datasourceConfig->returnValue("getColumns", [new Field("name"), new Field("age")]);

        $columnsTransformation = new ColumnsTransformation([new Field("name")]);

        $query = new SQLQuery("*", "test");
        $query = $this->columnsTransformationProcessor->updateQuery($columnsTransformation, $query, [], $datasource);
        $this->assertEquals("SELECT name FROM test", $query->getSQL());

        $query = new SQLQuery("*", "(SELECT * FROM test)");
        $query = $this->columnsTransformationProcessor->updateQuery($columnsTransformation, $query, [], $datasource);
        $this->assertEquals("SELECT name FROM (SELECT * FROM test)", $query->getSQL());

    }

    public function testCanAlterNonTrivialSelectStatementCorrectly() {

        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $datasource->returnValue("getConfig", $datasourceConfig);
        $datasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $datasourceConfig->returnValue("getColumns", [new Field("this"), new Field("that"), new Field("total")]);

        $columnsTransformation = new ColumnsTransformation([new Field("this"), new Field("total")]);
        $query = new SQLQuery("`this`, `that`, COUNT(*) `total`", "test");

        $query = $this->columnsTransformationProcessor->updateQuery($columnsTransformation, $query, [], $datasource);
        $this->assertEquals("SELECT C1.this,C1.total FROM (SELECT `this`, `that`, COUNT(*) `total` FROM test) C1", $query->getSQL());

    }

    public function testCanSelectColumnsWithSeriesOfTransformations() {

        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $datasource->returnValue("getConfig", $datasourceConfig);
        $datasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $datasourceConfig->returnValue("getColumns", [new Field("this"), new Field("that")]);

        $columnsTransformation = new ColumnsTransformation([new Field("this")]);
        $query = new SQLQuery("`this`, `that`", "test");
        $query->setWhereClause("`this` = 'steve'");

        $query = $this->columnsTransformationProcessor->updateQuery($columnsTransformation, $query, [], $datasource);
        $this->assertEquals("SELECT C1.this FROM (SELECT `this`, `that` FROM test WHERE `this` = 'steve') C1", $query->getSQL());

    }

    public function testCanApplyMultipleColumnsTransformations() {

        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $datasource->returnValue("getConfig", $datasourceConfig);
        $datasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $datasourceConfig->returnValue("getColumns", [new Field("this"), new Field("that"), new Field("the other")]);

        $firstColumnsTransformation = new ColumnsTransformation([new Field("this"), new Field("that")]);
        $secondColumnsTransformation = new ColumnsTransformation([new Field("that")]);

        $query = new SQLQuery("`this`, `that`, `the other`", "test");

        $query = $this->columnsTransformationProcessor->updateQuery($firstColumnsTransformation, $query, [], $datasource);
        $query = $this->columnsTransformationProcessor->updateQuery($secondColumnsTransformation, $query, [], $datasource);

        $this->assertEquals("SELECT C2.that FROM (SELECT C1.this,C1.that FROM (SELECT `this`, `that`, `the other` FROM test) C1) C2", $query->getSQL());

    }

    public function testCanRetainParameterValuesFromBaseQuery() {

        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $datasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $datasource->returnValue("getConfig", $datasourceConfig);
        $datasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $datasourceConfig->returnValue("getColumns", [new Field("this"), new Field("that")]);

        $columnsTransformation = new ColumnsTransformation([new Field("this")]);
        $query = new SQLQuery("*", "test", [4]);
        $query->setWhereClause("`this` = ?");

        $query = $this->columnsTransformationProcessor->updateQuery($columnsTransformation, $query, [], $datasource);
        $this->assertEquals("SELECT this FROM test WHERE `this` = ?", $query->getSQL());
        $this->assertEquals([4], $query->getParameters());
    }

}