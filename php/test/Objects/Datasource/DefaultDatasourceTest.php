<?php


namespace Kinintel\Test\Objects\Datasource;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\ValueObjects\Authentication\DefaultDatasourceCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;

include_once "autoloader.php";

class DefaultDatasourceTest extends \PHPUnit\Framework\TestCase {

    public function testDefaultDatasourceUsesDefaultDataSourceAuthenticationAndUniqueTablePerInstance() {

        $testDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);

        $defaultDatasource = new DefaultDatasource($testDatasource);
        $this->assertEquals(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "table_1"), $defaultDatasource->getConfig());
        $this->assertEquals(new DefaultDatasourceCredentials(), $defaultDatasource->getAuthenticationCredentials());


        // Check new table name created second time
        $defaultDatasource = new DefaultDatasource($testDatasource);
        $this->assertEquals(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "table_2"), $defaultDatasource->getConfig());
        $this->assertEquals(new DefaultDatasourceCredentials(), $defaultDatasource->getAuthenticationCredentials());


    }


    public function testSQLLiteDatabasePopulatedAsExpectedOnMaterialiseOfDefaultDatasourceWithKeyFieldsRemoved() {

        $tabularDataset = new ArrayTabularDataset([
            new Field("name", "Full Name", null, Field::TYPE_STRING, true),
            new Field("age", "Actual age", null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Bobby", "age" => 33],
            ["name" => "Mark", "age" => 44],
            ["name" => "Clare", "age" => 55]
        ]);

        $testDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $testDatasource->returnValue("materialise", $tabularDataset);

        $defaultDatasource = new DefaultDatasource($testDatasource);
        $dataSet = $defaultDatasource->materialise();

        $this->assertInstanceOf(SQLResultSetTabularDataset::class, $dataSet);
        $this->assertEquals([
            new Field("name", "Full Name", null, Field::TYPE_STRING),
            new Field("age", "Actual age", null, Field::TYPE_INTEGER)
        ], $dataSet->getColumns());

        $this->assertEquals(["name" => "Bobby", "age" => 33], $dataSet->nextDataItem());
        $this->assertEquals(["name" => "Mark", "age" => 44], $dataSet->nextDataItem());
        $this->assertEquals(["name" => "Clare", "age" => 55], $dataSet->nextDataItem());

        // Now compare with direct SQLite query
        $dbConnection = DefaultDatasource::getCredentials()->returnDatabaseConnection();
        $results = $dbConnection->query("SELECT * FROM table_3");
        $this->assertEquals(["name" => "Bobby", "age" => 33], $results->nextRow());
        $this->assertEquals(["name" => "Mark", "age" => 44], $results->nextRow());
        $this->assertEquals(["name" => "Clare", "age" => 55], $results->nextRow());

    }

    public function testCanConstructDefaultDatasetDirectlyWithDatasetInstead() {

        $tabularDataset = new ArrayTabularDataset([
            new Field("name", "Full Name", null, Field::TYPE_STRING),
            new Field("age", "Actual age", null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Bobby", "age" => 33],
            ["name" => "Mark", "age" => 44],
            ["name" => "Clare", "age" => 55]
        ]);

        $defaultDatasource = new DefaultDatasource($tabularDataset);
        $dataSet = $defaultDatasource->materialise();

        $this->assertInstanceOf(SQLResultSetTabularDataset::class, $dataSet);
        $this->assertEquals([
            new Field("name", "Full Name"),
            new Field("age", "Actual age", null, Field::TYPE_INTEGER)
        ], $dataSet->getColumns());

        $this->assertEquals(["name" => "Bobby", "age" => 33], $dataSet->nextDataItem());
        $this->assertEquals(["name" => "Mark", "age" => 44], $dataSet->nextDataItem());
        $this->assertEquals(["name" => "Clare", "age" => 55], $dataSet->nextDataItem());


    }

    public function testCanHandleAnEmptyDatasourceBeingReturnedOnMaterialiseOfSourceDataObject() {

        $mockDataObject = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $emptyDataset = new ArrayTabularDataset([],[]);

        $mockDataObject->returnValue("materialise", $emptyDataset, [[]]);

        $defaultDatasource = new DefaultDatasource($mockDataObject);

        $dataset = $defaultDatasource->materialise();

        $this->assertEquals([], $dataset->getAllData());

    }


}