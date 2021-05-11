<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\ResultSet\ResultSet;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SQLTransformationProcessor;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Query\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Query\FilterQuery;
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
     * @var DatabaseConnection
     */
    private $databaseConnection;


    // Setup
    public function setUp(): void {


        $this->databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $this->authCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $this->authCredentials->returnValue("returnDatabaseConnection", $this->databaseConnection);

        $this->validator = MockObjectProvider::instance()->getMockInstance(Validator::class);

    }


    public function testCanMaterialiseDataSetForUntransformedTableDatasource() {


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data"),
            $this->authCredentials, $this->validator);


        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->databaseConnection->returnValue("query", $resultSet, [
            "SELECT * FROM test_data", []
        ]);

        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset();

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet), $dataSet);
    }


    public function testCanMaterialiseDataSetForUntransformedQueryDatasource() {


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "", "SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id"),
            $this->authCredentials, $this->validator);


        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->databaseConnection->returnValue("query", $resultSet, [
            "SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id", []
        ]);

        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset();

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet), $dataSet);
    }


    public function testCanMaterialiseTableBasedDataSetWithSQLDatabaseTransformationsApplied() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data"),
            $this->authCredentials, $this->validator);


        $transformation1 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation2 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation3 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);

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

        $transformationProcessor->returnValue("updateQuery", new SQLQuery("SELECT * FROM test_data WHERE id = ?", [1]), [
            $transformation1, new SQLQuery("SELECT * FROM test_data", []), null
        ]);

        $transformationProcessor->returnValue("updateQuery", new SQLQuery("SELECT * FROM test_data WHERE id = ? AND name = ?", [1, "Mark"]), [
            $transformation2, new SQLQuery("SELECT * FROM test_data WHERE id = ?", [1]), [$transformation1]
        ]);

        $transformationProcessor2->returnValue("updateQuery", new SQLQuery("SELECT * FROM test_data WHERE id = ? AND name = ? AND age = ?", [1, "Mark", 33]), [
            $transformation3, new SQLQuery("SELECT * FROM test_data WHERE id = ? AND name = ?", [1, "Mark"]), [$transformation2, $transformation1]
        ]);

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->databaseConnection->returnValue("query", $resultSet, [
            "SELECT * FROM test_data WHERE id = ? AND name = ? AND age = ?", [1, "Mark", 33]
        ]);


        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset();

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet), $dataSet);


    }


    public function testCanMaterialiseQueryBasedDataSetWithSQLDatabaseTransformationsApplied() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "", "SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id"),
            $this->authCredentials, $this->validator);


        $transformation1 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation2 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);
        $transformation3 = MockObjectProvider::instance()->getMockInstance(SQLDatabaseTransformation::class);

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

        $transformationProcessor->returnValue("updateQuery", new SQLQuery("SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id) A WHERE id = ?", [1]), [
            $transformation1, new SQLQuery("SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id) A", []), null
        ]);

        $transformationProcessor->returnValue("updateQuery", new SQLQuery("SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id) A WHERE id = ? AND name = ?", [1, "Mark"]), [
            $transformation2, new SQLQuery("SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id) A WHERE id = ?", [1]), [$transformation1]
        ]);

        $transformationProcessor2->returnValue("updateQuery", new SQLQuery("SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id) A WHERE id = ? AND name = ? AND age = ?", [1, "Mark", 33]), [
            $transformation3, new SQLQuery("SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id) A WHERE id = ? AND name = ?", [1, "Mark"]), [$transformation2, $transformation1]
        ]);

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $this->databaseConnection->returnValue("query", $resultSet, [
            "SELECT * FROM (SELECT * FROM test_data d LEFT JOIN other_table o ON d.id = o.test_id) A WHERE id = ? AND name = ? AND age = ?", [1, "Mark", 33]
        ]);


        /**
         * @var SQLResultSetTabularDataset $dataSet
         */
        $dataSet = $sqlDatabaseDatasource->materialiseDataset();

        $this->assertEquals(new SQLResultSetTabularDataset($resultSet), $dataSet);


    }


    public function testUpdateExceptionThrownIfAttemptToUpdateNoneUpdatableDatasource() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data"),
            $this->authCredentials, $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);

        try {
            $sqlDatabaseDatasource->update($dataSet);
            $this->fail("Should have thrown here");
        } catch (DatasourceUpdateException $e) {
            $this->assertTrue(true);
        }

    }


    public function testUpdateExceptionThrownIfAttemptToUpdateDatasourceWithQuery() {

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_QUERY, "", "SELECT * FROM test", true),
            $this->authCredentials, $this->validator);

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
            $this->authCredentials, $this->validator);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);

        try {
            $sqlDatabaseDatasource->update($dataSet);
            $this->fail("Should have thrown here");
        } catch (DatasourceUpdateException $e) {
            $this->assertTrue(true);
        }

    }


}