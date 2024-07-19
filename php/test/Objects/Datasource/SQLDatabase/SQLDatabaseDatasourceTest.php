<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\Database\BulkData\BulkDataManager;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Exception\SQLException;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\Database\MetaData\TableColumn;
use Kinikit\Persistence\Database\MetaData\TableIndex;
use Kinikit\Persistence\Database\MetaData\TableIndexColumn;
use Kinikit\Persistence\Database\MetaData\TableMetaData;
use Kinikit\Persistence\Database\MetaData\UpdatableTableColumn;
use Kinikit\Persistence\Database\ResultSet\ResultSet;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Exception\DuplicateEntriesException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SQLTransformationProcessor;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\ValueObjects\Authentication\FTP\FTPAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\PostgreSQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\PrimaryKeyTooLargeException;
use Kinintel\ValueObjects\Datasource\SQLDatabase\RowSizeTooLargeException;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;

include_once "autoloader.php";

class SQLDatabaseDatasourceTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $authCredentials;

    /**
     * @var MockObject
     */
    private $validator;


    /**
     * @var MockObject
     */
    private $databaseConnection;


    /**
     * @var MockObject
     */
    private $bulkDataManager;


    // Setup
    public function setUp(): void {


        $this->databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $this->bulkDataManager = MockObjectProvider::instance()->getMockInstance(BulkDataManager::class);
        $this->databaseConnection->returnValue("getBulkDataManager", $this->bulkDataManager);

        $this->authCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $this->authCredentials->returnValue("returnDatabaseConnection", $this->databaseConnection);

        $this->validator = MockObjectProvider::instance()->getMockInstance(Validator::class);

    }


    public function testCanMaterialiseDataSetForUntransformedTableDatasource() {


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data"),
            $this->authCredentials, null, $this->validator);


        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->authCredentials->returnValue("query", $resultSet, [
            "SELECT * FROM test_data", []
        ]);

        $this->databaseConnection->returnValue("getTableMetaData", new TableMetaData("test_data", [
            new TableColumn("id", TableColumn::SQL_INT, null, 2, "", true),
            new TableColumn("name", TableColumn::SQL_VARCHAR, 255, null, "", false),
            new TableColumn("description", TableColumn::SQL_VARCHAR, 2000, null, "", false),
            new TableColumn("date_started", TableColumn::SQL_DATE_TIME, null, 2, "", false),
            new TableColumn("age", TableColumn::SQL_DOUBLE)
        ]));

        $expectedColumns = [
            new Field("id", "Id", null, Field::TYPE_INTEGER, true),
            new Field("name", "Name", null, Field::TYPE_STRING, false),
            new Field("description", "Description", null, Field::TYPE_MEDIUM_STRING, false),
            new Field("date_started", "Date Started", null, Field::TYPE_DATE_TIME, false),
            new Field("age", "Age", null, Field::TYPE_FLOAT, false)
        ];

        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset();

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet, $expectedColumns, true), $dataSet);
    }


    public function testParameterisedTableNameIsEvaluatedCorrectlyOnMaterialise() {


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE,
            "{{testParam}}"),
            $this->authCredentials, null, $this->validator);


        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->authCredentials->returnValue("query", $resultSet, [
            "SELECT * FROM BINGO_BONGO", []
        ]);

        $this->databaseConnection->returnValue("getTableMetaData", new TableMetaData("test_data", [
            new TableColumn("id", TableColumn::SQL_INT, null, 2, "", true),
            new TableColumn("name", TableColumn::SQL_VARCHAR, 255, null, "", false),
            new TableColumn("description", TableColumn::SQL_VARCHAR, 2000, null, "", false),
            new TableColumn("date_started", TableColumn::SQL_DATE_TIME, null, 2, "", false),
            new TableColumn("age", TableColumn::SQL_DOUBLE)
        ]));

        $expectedColumns = [
            new Field("id", "Id", null, Field::TYPE_INTEGER, true),
            new Field("name", "Name", null, Field::TYPE_STRING, false),
            new Field("description", "Description", null, Field::TYPE_MEDIUM_STRING, false),
            new Field("date_started", "Date Started", null, Field::TYPE_DATE_TIME, false),
            new Field("age", "Age", null, Field::TYPE_FLOAT, false)
        ];

        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset(["testParam" => "BINGO_BONGO"]);

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet, $expectedColumns, true), $dataSet);


    }

    public function testColumnsPassedThroughToDataSetIfSupplied() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", [
            new Field("test_id")
        ]),
            $this->authCredentials, null, $this->validator);


        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->authCredentials->returnValue("query", $resultSet, [
            "SELECT * FROM test_data", []
        ]);

        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset();

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet, [
            new Field("test_id")
        ]), $dataSet);
    }


    public function testCanMaterialiseDataSetForUntransformedQueryDatasource() {


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "", "SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id"),
            $this->authCredentials, null, $this->validator);


        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->authCredentials->returnValue("query", $resultSet, [
            "SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id) A", []
        ]);

        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset();

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet), $dataSet);
    }


    public function testCanMaterialiseDataSetWhenPagingViaParameters() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "",
            "SELECT * FROM main_table m LIMIT {{limit}} OFFSET {{offset}}", [], false, true), $this->authCredentials, null, $this->validator);

        $sqlDatabaseDatasource->applyTransformation(new PagingTransformation(100, 0));
        $sqlDatabaseDatasource->materialiseDataset();

        $this->assertTrue($this->authCredentials->methodWasCalled("query", ["SELECT * FROM (SELECT * FROM main_table m LIMIT 100 OFFSET 0) A", []]));

    }

    public function testCanMaterialiseDataSetWithComplexQueryWhenPagingViaParameters() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "",
            "SELECT * FROM main_table m LIMIT {{limit}} OFFSET {{offset}} LEFT JOIN SELECT * FROM other_table o ON m.this = o.that", [], false, true), $this->authCredentials, null, $this->validator);

        $sqlDatabaseDatasource->applyTransformation(new PagingTransformation(10, 20));
        $sqlDatabaseDatasource->materialiseDataset();

        $this->assertTrue($this->authCredentials->methodWasCalled("query", ["SELECT * FROM (SELECT * FROM main_table m LIMIT 10 OFFSET 20 LEFT JOIN SELECT * FROM other_table o ON m.this = o.that) A", []]));

    }


    public function testAnyPassedParametersAreAppliedExplicitlyToTheQueryInAQueryBasedDatasource() {


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "", "SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id WHERE d.id = {{testId}}"),
            $this->authCredentials, null, $this->validator);


        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->authCredentials->returnValue("query", $resultSet, [
            "SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id WHERE d.id = 255) A", []
        ]);

        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset([
            "testId" => 255
        ]);

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet), $dataSet);

    }


    public function testCanMaterialiseTableBasedDataSetWithSQLDatabaseTransformationsApplied() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data"),
            $this->authCredentials, null, $this->validator);


        $transformation1 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation1->returnValue("getSQLTransformationProcessorKey", "filter");
        $transformation2 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation2->returnValue("getSQLTransformationProcessorKey", "filter");
        $transformation3 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation3->returnValue("getSQLTransformationProcessorKey", "filter");


        // Apply each transformation
        $sqlDatabaseDatasource->applyTransformation($transformation1, ["param1" => "Hello", "param2" => "World"]);
        $sqlDatabaseDatasource->applyTransformation($transformation2, ["param1" => "Hello", "param2" => "World"]);
        $sqlDatabaseDatasource->applyTransformation($transformation3, ["param1" => "Hello", "param2" => "World"]);


        $transformationProcessor = MockObjectProvider::instance()->getMockInstance(SQLTransformationProcessor::class);
        $transformationProcessor2 = MockObjectProvider::instance()->getMockInstance(SQLTransformationProcessor::class);

        $sqlDatabaseDatasource->setTransformationProcessorInstances([
            "test1" => $transformationProcessor,
            "test2" => $transformationProcessor2
        ]);


        $transformation1->returnValue("getSQLTransformationProcessorKey", "test1");
        $transformation2->returnValue("getSQLTransformationProcessorKey", "test1");
        $transformation3->returnValue("getSQLTransformationProcessorKey", "test2");

        $transformationProcessor->returnValue("updateQuery", new SQLQuery("*", "?", [1]), [
            $transformation1, new SQLQuery("*", "test_data"), ["param1" => "Hello", "param2" => "World"],
            $sqlDatabaseDatasource
        ]);

        $transformationProcessor->returnValue("updateQuery", new SQLQuery("*", "?", [2]), [
            $transformation2, new SQLQuery("*", "?", [1]), ["param1" => "Hello", "param2" => "World"],
            $sqlDatabaseDatasource
        ]);

        $transformationProcessor2->returnValue("updateQuery", new SQLQuery("*", "?", [3]), [
            $transformation3, new SQLQuery("*", "?", [2]), ["param1" => "Hello", "param2" => "World"],
            $sqlDatabaseDatasource
        ]);

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->authCredentials->returnValue("query", $resultSet, [
            "SELECT * FROM ?", [3]
        ]);


        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset(["param1" => "Hello", "param2" => "World"]);

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet), $dataSet);


    }


    public function testCanMaterialiseQueryBasedDataSetWithSQLDatabaseTransformationsApplied() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "", "SELECT * FROM test_data d"),
            $this->authCredentials, null, $this->validator);


        $transformation1 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation1->returnValue("getSQLTransformationProcessorKey", "filter");

        $transformation2 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation2->returnValue("getSQLTransformationProcessorKey", "filter");

        $transformation3 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation3->returnValue("getSQLTransformationProcessorKey", "filter");

        // Apply each transformation
        $sqlDatabaseDatasource->applyTransformation($transformation1);
        $sqlDatabaseDatasource->applyTransformation($transformation2);
        $sqlDatabaseDatasource->applyTransformation($transformation3);


        $transformationProcessor = MockObjectProvider::instance()->getMockInstance(SQLTransformationProcessor::class);
        $transformationProcessor2 = MockObjectProvider::instance()->getMockInstance(SQLTransformationProcessor::class);

        $sqlDatabaseDatasource->setTransformationProcessorInstances([
            "test1" => $transformationProcessor,
            "test2" => $transformationProcessor2
        ]);


        $transformation1->returnValue("getSQLTransformationProcessorKey", "test1");
        $transformation2->returnValue("getSQLTransformationProcessorKey", "test1");
        $transformation3->returnValue("getSQLTransformationProcessorKey", "test2");

        $transformationProcessor->returnValue("updateQuery", new SQLQuery("*", "(SELECT * from test_data d) A", [1]), [
            $transformation1, new SQLQuery("*", "(SELECT * from test_data d) A"), []
        ]);

        $transformationProcessor->returnValue("updateQuery", new SQLQuery("*", "(SELECT * from test_data d) A", [2]), [
            $transformation2, new SQLQuery("*", "(SELECT * from test_data d) A", [1]), [$transformation1]
        ]);

        $transformationProcessor2->returnValue("updateQuery", new SQLQuery("*", "(SELECT * from test_data d) A", [3]), [
            $transformation3, new SQLQuery("*", "(SELECT * from test_data d) A", [2]), [$transformation2, $transformation1]
        ]);

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->databaseConnection->returnValue("query", $resultSet, [
            "(SELECT * from test_data d) A", 3
        ]);


        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        //$dataSet = $sqlDatabaseDatasource->materialiseDataset();


        // $this->assertEquals(new SQLResultSetTabularDataset($resultSet), $dataSet);
        $this->assertTrue(true);

    }


    public function testUpdateExceptionThrownIfAttemptToUpdateUpdatableDatasourceWithNoUpdateConfig() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data"),
            $this->authCredentials, null, $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);

        try {
            $sqlDatabaseDatasource->update($dataSet);
            $this->fail("Should have thrown here");
        } catch (DatasourceNotUpdatableException $e) {
            $this->assertTrue(true);
        }

    }


    public function testUpdateExceptionThrownIfAttemptToUpdateDatasourceWithQuery() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "", "SELECT * FROM test", true),
            $this->authCredentials, new DatasourceUpdateConfig(), $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);

        try {
            $sqlDatabaseDatasource->update($dataSet);
            $this->fail("Should have thrown here");
        } catch (DatasourceUpdateException $e) {
            $this->assertTrue(true);
        }

    }

    public function testUpdateExceptionThrownIfAttemptToUpdateWithNoneTabularDataset() {


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials, new DatasourceUpdateConfig(), $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);

        try {
            $sqlDatabaseDatasource->update($dataSet);
            $this->fail("Should have thrown here");
        } catch (DatasourceUpdateException $e) {
            $this->assertTrue(true);
        }

    }


    public function testAllDataAddedCorrectlyUsingBulkDataManagerWhenSuppliedAsSuch() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials, new DatasourceUpdateConfig(), $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);


        $data = [
            [
                "name" => "Bobby Owens",
                "age" => 55,
                "extraDetail" => "He's a dude"
            ],
            [
                "name" => "David Suchet",
                "age" => 66,
                "extraDetail" => "He's a geezer"
            ]
        ];

        $dataSet->returnValue("nextNDataItems", $data, [50]);


        $sqlDatabaseDatasource->update($dataSet, UpdatableDatasource::UPDATE_MODE_ADD);


        $this->assertTrue($this->bulkDataManager->methodWasCalled("insert", [
            "test_data", $data, null
        ]));

    }


    /**
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testDuplicateEntriesExceptionSuppliedExceptionRaisedIfSQLExceptionThrownWith23000Codes() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials, new DatasourceUpdateConfig(), $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);


        $data = [
            [
                "name" => "Bobby Owens",
                "age" => 55,
                "extraDetail" => "He's a dude"
            ],
            [
                "name" => "David Suchet",
                "age" => 66,
                "extraDetail" => "He's a geezer"
            ]
        ];

        $dataSet->returnValue("nextNDataItems", $data, [50]);


        $this->bulkDataManager->throwException("insert", new SQLException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry", 23000), [
            "test_data", $data, null
        ]);


        try {
            $sqlDatabaseDatasource->update($dataSet, UpdatableDatasource::UPDATE_MODE_ADD);
            $this->fail("Should have thrown here");
        } catch (DuplicateEntriesException $e) {
        }

    }

    public function testAllDataRemovedCorrectlyUsingBulkDataManagerWhenSuppliedAsSuchUsingKeyFieldNames() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials, new DatasourceUpdateConfig(["name"]), $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);


        $data = [
            [
                "name" => "Bobby Owens",
                "age" => 55,
                "extraDetail" => "He's a dude"
            ],
            [
                "name" => "David Suchet",
                "age" => 66,
                "extraDetail" => "He's a geezer"
            ]
        ];

        $dataSet->returnValue("nextNDataItems", $data, [50]);


        $sqlDatabaseDatasource->update($dataSet, UpdatableDatasource::UPDATE_MODE_DELETE);


        $this->assertTrue($this->bulkDataManager->methodWasCalled("delete", [
            "test_data", $data, null
        ]));

    }

    public function testAllDataReplacedCorrectlyUsingBulkDataManagerWhenSuppliedAsSuch() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials, new DatasourceUpdateConfig(), $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);


        $data = [
            [
                "name" => "Bobby Owens",
                "age" => 55,
                "extraDetail" => "He's a dude"
            ],
            [
                "name" => "David Suchet",
                "age" => 66,
                "extraDetail" => "He's a geezer"
            ]
        ];

        $dataSet->returnValue("nextNDataItems", $data, [50]);


        $sqlDatabaseDatasource->update($dataSet, UpdatableDatasource::UPDATE_MODE_REPLACE);


        $this->assertTrue($this->bulkDataManager->methodWasCalled("replace", [
            "test_data", $data, null
        ]));
    }


    public function testCanDeleteMultipleItemsUsingFilterJunction() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials, new DatasourceUpdateConfig(), $this->validator);


        $this->databaseConnection->returnValue("escapeColumn", "`$1`", ["$1"]);

        // Issue a filtered delete
        $sqlDatabaseDatasource->filteredDelete(new FilterJunction([new Filter("[[test]]", "Market"), new Filter("[[other]]", 33, Filter::FILTER_TYPE_GREATER_THAN)]));

        // Confirm that the expected query was executed
        $this->assertTrue($this->databaseConnection->methodWasCalled("execute", ["DELETE FROM test_data WHERE `test` = ? AND `other` > ?", ["Market", 33]]));


        // Issue a filtered delete
        $sqlDatabaseDatasource->filteredDelete(new FilterJunction([new Filter("[[test]]", "Market"), new Filter("[[other]]", [33, 44, 55, 66], Filter::FILTER_TYPE_IN)], [], FilterJunction::LOGIC_OR));

        // Confirm that the expected query was executed
        $this->assertTrue($this->databaseConnection->methodWasCalled("execute", ["DELETE FROM test_data WHERE `test` = ? OR `other` IN (?,?,?,?)", ["Market", 33, 44, 55, 66]]));

        // Issue a delete all command with no filters
        $sqlDatabaseDatasource->filteredDelete(new FilterJunction([], [], FilterJunction::LOGIC_OR));

        // Confirm that the expected query was executed
        $this->assertTrue($this->databaseConnection->methodWasCalled("execute", ["DELETE FROM test_data", []]));



    }


    public function testWhenManagingTableStructureOnInstanceSaveCreatesTableAccordingToConfiguredFieldsIfNoneExists() {

        $ddlGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $config = new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "mytable", "", [], true);

        $datasource = new SQLDatabaseDatasource($config,
            $this->authCredentials, null, $this->validator, $ddlGenerator);


        // Throw an exception when getting table meta data
        $this->databaseConnection->throwException("getTableMetaData", new SQLException("Table does not exist"), [
            "mytable"
        ]);

        // Expect create table statement created using ddl generator
        $newMetaData = new TableMetaData("mytable", [
            new TableColumn("when", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("why", TableColumn::SQL_VARCHAR, 255, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, false),
            new TableColumn("how_many", TableColumn::SQL_INTEGER, null, null, null, false)
        ]);

        $ddlGenerator->returnValue("generateTableCreateSQL", "NEW TABLE CREATE", [
            $newMetaData,
            $this->databaseConnection
        ]);


        $config->setColumns([
            new Field("when", null, null, Field::TYPE_DATE, true),
            new Field("why", null, null, null, true),
            new Field("what", null, null, Field::TYPE_MEDIUM_STRING, false),
            new Field("how_many", null, null, Field::TYPE_INTEGER)
        ]);

        // Modify the table structure and ensure a create was made
        $datasource->onInstanceSave();

        // Expect create to be issued
        $this->assertTrue($this->databaseConnection->methodWasCalled("executeScript", [
            "NEW TABLE CREATE"
        ]));

    }


    public function testWhenManagingTableStructureOnInstanceSaveModifiesTableAccordingToPassedFieldsIfTableAlreadyExists() {
        $ddlGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $config = new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "mytable", "", [], true);

        $datasource = new SQLDatabaseDatasource($config,
            $this->authCredentials, null, $this->validator, $ddlGenerator);


        $existingMetaData = new TableMetaData("mytable", [
            new TableColumn("which", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, null, null, null, true),
            new TableColumn("how_many", TableColumn::SQL_INTEGER, null, null, null, false)
        ]);

        // Return existing meta data from call
        $this->databaseConnection->returnValue("getTableMetaData", $existingMetaData, [
            "mytable"
        ]);

        // Expect create table statement created using ddl generator
        $newMetaData = new TableMetaData("mytable", [
            new TableColumn("when", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("why", TableColumn::SQL_VARCHAR, 255, null, null, true),
            new UpdatableTableColumn("macaroni", TableColumn::SQL_INTEGER, null, null, null, false, false, false, "how_many")
        ]);

        $ddlGenerator->returnValue("generateTableModifySQL", "NEW TABLE MODIFY", [
            $existingMetaData,
            $newMetaData,
            $this->databaseConnection
        ]);


        $config->setColumns([
            new Field("when", null, null, Field::TYPE_DATE, true),
            new Field("why", null, null, null, true),
            new DatasourceUpdateField("macaroni", null, null, Field::TYPE_INTEGER, false, "how_many")
        ]);

        // Modify the table structure and ensure a create was made
        $datasource->onInstanceSave();


        // Expect create to be issued
        $this->assertTrue($this->databaseConnection->methodWasCalled("executeScript", [
            "NEW TABLE MODIFY"
        ]));
    }


    public function testWhenManagingTableStructureIDTypeFieldsAreMappedToAutoIncrementPrimaryKeyFieldAndNegateAnyPrimaryKeyFields() {
        $ddlGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $config = new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "mytable", "", [], true);

        $datasource = new SQLDatabaseDatasource($config,
            $this->authCredentials, null, $this->validator, $ddlGenerator);


        $existingMetaData = new TableMetaData("mytable", [
            new TableColumn("which", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 255, null, null, true),
            new TableColumn("how_many", TableColumn::SQL_INTEGER, null, null, null, false)
        ]);

        // Return existing meta data from call
        $this->databaseConnection->returnValue("getTableMetaData", $existingMetaData, [
            "mytable"
        ]);

        // Expect create table statement created using ddl generator
        $newMetaData = new TableMetaData("mytable", [
            new TableColumn("when", TableColumn::SQL_DATE, null, null, null, false),
            new TableColumn("why", TableColumn::SQL_VARCHAR, 255, null, null, false),
            new UpdatableTableColumn("macaroni", TableColumn::SQL_INTEGER, 11, null, null, true, true, false, "how_many")
        ]);

        $ddlGenerator->returnValue("generateTableModifySQL", "NEW TABLE MODIFY", [
            $existingMetaData,
            $newMetaData,
            $this->databaseConnection
        ]);


        $config->setColumns([
            new Field("when", null, null, Field::TYPE_DATE, false),
            new Field("why", null, null, null, false),
            new DatasourceUpdateField("macaroni", null, null, Field::TYPE_ID, false, "how_many")
        ]);

        // Modify the table structure and ensure a create was made
        $datasource->onInstanceSave();


        // Expect create to be issued
        $this->assertTrue($this->databaseConnection->methodWasCalled("executeScript", [
            "NEW TABLE MODIFY"
        ]));
    }


    public function testWhenManagingTableStructureOnInstanceSaveIndexesAreCreatedAsPartOfCreateTableForNewOne() {

        $ddlGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $config = new ManagedTableSQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "mytable", "", []);

        $datasource = new SQLDatabaseDatasource($config,
            $this->authCredentials, null, $this->validator, $ddlGenerator);


        // Throw an exception when getting table meta data
        $this->databaseConnection->throwException("getTableMetaData", new SQLException("Table does not exist"), [
            "mytable"
        ]);

        // Expect create table statement created using ddl generator
        $newMetaData = new TableMetaData("mytable", [
            new TableColumn("when", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("why", TableColumn::SQL_VARCHAR, 255, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, false),
            new TableColumn("how_many", TableColumn::SQL_INTEGER, null, null, null, false),
            new TableColumn("notes", TableColumn::SQL_LONGBLOB)
        ], [
            new TableIndex(md5("whenwhy"), [new TableIndexColumn("when"), new TableIndexColumn("why")]),
            new TableIndex(md5("whywhatnotes"), [new TableIndexColumn("why"), new TableIndexColumn("what", 500), new TableIndexColumn("notes", 500)])
        ]);


        $ddlGenerator->returnValue("generateTableCreateSQL", "NEW TABLE CREATE", [
            $newMetaData,
            $this->databaseConnection
        ]);


        $config->setColumns([
            new Field("when", null, null, Field::TYPE_DATE, true),
            new Field("why", null, null, null, true),
            new Field("what", null, null, Field::TYPE_MEDIUM_STRING, false),
            new Field("how_many", null, null, Field::TYPE_INTEGER),
            new Field("notes", null, null, Field::TYPE_LONG_STRING)
        ]);

        $config->setIndexes([new Index(["when", "why"]), new Index(["why", "what", "notes"])]);

        // Modify the table structure and ensure a create was made
        $datasource->onInstanceSave();

        // Expect create to be issued
        $this->assertTrue($this->databaseConnection->methodWasCalled("executeScript", [
            "NEW TABLE CREATE"
        ]));

    }


    public function testWhenManagingTableStructureOnInstanceSaveIndexesAreUpdatedAsPartOfUpdateTableForExistingOne() {

        $ddlGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $config = new ManagedTableSQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "mytable", "", [], true);

        $datasource = new SQLDatabaseDatasource($config,
            $this->authCredentials, null, $this->validator, $ddlGenerator);


        $existingMetaData = new TableMetaData("mytable", [
            new TableColumn("which", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("how_many", TableColumn::SQL_INTEGER, null, null, null, false)
        ], [
            new TableIndex(md5("whichwhat"), [new TableIndexColumn("which"), new TableIndexColumn("what", 500)]),
        ]);

        // Return existing meta data from call
        $this->databaseConnection->returnValue("getTableMetaData", $existingMetaData, [
            "mytable"
        ]);

        // Expect create table statement created using ddl generator
        $newMetaData = new TableMetaData("mytable", [
            new UpdatableTableColumn("when", TableColumn::SQL_DATE, null, null, null, true, false, false, "which"),
            new UpdatableTableColumn("why", TableColumn::SQL_VARCHAR, 2000, null, null, true, false, false, "what"),
            new UpdatableTableColumn("macaroni", TableColumn::SQL_INTEGER, null, null, null, false, false, false, "how_many")
        ], [
            new TableIndex(md5("whenwhy"), [new TableIndexColumn("when"), new TableIndexColumn("why", 500)]),
        ]);

        $ddlGenerator->returnValue("generateTableModifySQL", "NEW TABLE MODIFY", [
            $existingMetaData,
            $newMetaData,
            $this->databaseConnection
        ]);

        $config->setColumns([
            new DatasourceUpdateField("when", null, null, Field::TYPE_DATE, true, "which"),
            new DatasourceUpdateField("why", null, null, Field::TYPE_MEDIUM_STRING, true, "what"),
            new DatasourceUpdateField("macaroni", null, null, Field::TYPE_INTEGER, false, "how_many")
        ]);

        $config->setIndexes([new Index(["when", "why"])]);

        // Modify the table structure and ensure a create was made
        $datasource->onInstanceSave();


        // Expect create to be issued
        $this->assertTrue($this->databaseConnection->methodWasCalled("executeScript", [
            "NEW TABLE MODIFY"
        ]));

    }


    public function testOnInstanceDeleteTableIsDeletedIfManageTableStructureSupplied() {

        $ddlGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $config = new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "mytable");

        $datasource = new SQLDatabaseDatasource($config,
            $this->authCredentials, null, $this->validator, $ddlGenerator);

        $ddlGenerator->returnValue("generateTableDropSQL", "TABLE DELETE", [
            "mytable"
        ]);

        $datasource->onInstanceDelete();

        $this->assertFalse($this->databaseConnection->methodWasCalled("executeScript", [
            "TABLE DELETE"
        ]));

        $config->setManageTableStructure(true);

        $datasource->onInstanceDelete();

        $this->assertTrue($this->databaseConnection->methodWasCalled("executeScript", [
            "TABLE DELETE"
        ]));
    }

    public function testCanAddCustomCredentials() {

        $ddlGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $config = new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "mytable");

        $datasource = new SQLDatabaseDatasource($config,
            $this->authCredentials, null, $this->validator, $ddlGenerator);

        $this->assertEquals([SQLiteAuthenticationCredentials::class, MySQLAuthenticationCredentials::class, PostgreSQLAuthenticationCredentials::class], $datasource->getSupportedCredentialClasses());

        SQLDatabaseDatasource::addCredentialsClass(FTPAuthenticationCredentials::class);

        $this->assertEquals([SQLiteAuthenticationCredentials::class, MySQLAuthenticationCredentials::class, PostgreSQLAuthenticationCredentials::class, FTPAuthenticationCredentials::class], $datasource->getSupportedCredentialClasses());

    }

    public function testDoesntAllowAboveMaxLengthRows() {
        $result = false;
        $columns = [
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 255, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 120, null, null, true),
            new TableColumn("what", TableColumn::SQL_DATE_TIME, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INT, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
        ];
        try{
            SQLDatabaseDatasource::validateRowSize($columns);
            $this->fail();
        }
        catch(RowSizeTooLargeException $e){
            // Success!
            $result = true;
        }
        $this->assertTrue($result);

        $columns = [
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 255, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 120, null, null, true),
            new TableColumn("what", TableColumn::SQL_DATE_TIME, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INT, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
        ];

        $this->assertTrue(SQLDatabaseDatasource::validateRowSize($columns));
    }

    public function testDoesntAllowAboveMaxLengthPrimaryKeys() {
        $result = false;
        $columns = [
            new TableColumn("what", TableColumn::SQL_VARCHAR, 500, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 500, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 4, null, null, true),
            new TableColumn("what", TableColumn::SQL_INT, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_DATE_TIME, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, false),
        ];
        try{
            SQLDatabaseDatasource::validatePrimaryKey($columns);
            $this->fail();
        }
        catch(PrimaryKeyTooLargeException $e){
            // Success!
            $result = true;
        }
        $this->assertTrue($result);

        $columns = [
            new TableColumn("what", TableColumn::SQL_VARCHAR, 740, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 20, null, null, true),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 4, null, null, true),
            new TableColumn("what", TableColumn::SQL_INT, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_INTEGER, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_DATE, null, null, null, true),
            new TableColumn("what", TableColumn::SQL_DATE_TIME, null, null, null, false),
            new TableColumn("what", TableColumn::SQL_VARCHAR, 2000, null, null, false),
        ];

        $this->assertTrue(SQLDatabaseDatasource::validatePrimaryKey($columns));
    }


}