<?php

namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\WebService\WebServiceDatasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Combine\CombineTransformation;
use Kinintel\ValueObjects\Transformation\TestTransformation;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class CombineTransformationProcessorTest extends TestCase {
    /**
     * @var MockObject
     */
    private $dataSourceService;

    /**
     * @var MockObject
     */
    private $dataSetService;


    /**
     * @var MockObject
     */
    private $authCredentials;

    /**
     * @var MockObject
     */
    private $validator;

    /**
     * @var CombineTransformationProcessor
     */
    private $processor;


    public function setUp(): void {
        $this->authCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $this->dataSourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->dataSetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->processor = new CombineTransformationProcessor($this->dataSourceService, $this->dataSetService);
        $this->validator = MockObjectProvider::instance()->getMockInstance(Validator::class);
    }


    public function testDatasourcesOfSameTypeResolvedCorrectly() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);

        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasourceInstance->returnValue("returnDataSource", $combineDatasource);

        $mainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);

        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $combineDatasourceInstance, ["testsource"]);

        $combineTransformation = new CombineTransformation("testsource");

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $dataSource = $this->processor->applyTransformation($combineTransformation, $mainDatasource);


        $this->assertTrue($dataSource instanceof SQLDatabaseDatasource);
        $this->assertTrue($combineTransformation->returnEvaluatedDataSource() instanceof SQLDatabaseDatasource);

    }

    public function testCombineDatasourceOfDifferentTypeResolvedCorrectly() {

        // Create sets of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $otherAuthenticationCredentials = MockObjectProvider::instance()->getMockInstance(BasicAuthenticationCredentials::class);

        $combineDatasource = MockObjectProvider::instance()->getMockInstance(WebServiceDatasource::class);
        $combineDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $otherAuthenticationCredentials);
        $combineDatasource->returnValue("materialise", new ArrayTabularDataset([new Field("field")], []));
        $combineDatasourceInstance->returnValue("returnDataSource", $combineDatasource);

        $mainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);

        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $combineDatasourceInstance, ["testsource"]);

        $combineTransformation = new CombineTransformation("testsource");

        $combineTransformation->setEvaluatedDataSource($combineDatasource);

        $liveValue = Container::instance()->get(DatasourceService::class);
        Container::instance()->set(DatasourceService::class, $this->dataSourceService);

        $dataSource = $this->processor->applyTransformation($combineTransformation, $mainDatasource);

        Container::instance()->set(DatasourceService::class, $liveValue);


        $this->assertTrue($dataSource instanceof DefaultDatasource);
        $this->assertTrue($combineTransformation->returnEvaluatedDataSource() instanceof DefaultDatasource);

    }


    public function testCanUpdateQuerySuccessfullyForUnion() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasource->returnValue("buildQuery", new SQLQuery("*", "combine_table"), [
            []
        ]);
        $combineDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $combineDatasource->returnValue("getConfig", $combineDatasourceConfig);
        $combineDatasourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try simple non aliased columns
        $combineTransformation = new CombineTransformation("testsource", null, CombineTransformation::COMBINE_TYPE_UNION);

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $sqlQuery = $this->processor->updateQuery($combineTransformation, new SQLQuery("*", "test_table"), [], $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.* FROM (SELECT * FROM test_table) T1 UNION SELECT T2.* FROM (SELECT * FROM combine_table) T2) T3"),
            $sqlQuery);
    }

    public function testCanUpdateQuerySuccessfullyForUnionAll() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasource->returnValue("buildQuery", new SQLQuery("*", "combine_table"), [
            []
        ]);
        $combineDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $combineDatasource->returnValue("getConfig", $combineDatasourceConfig);
        $combineDatasourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try simple non aliased columns
        $combineTransformation = new CombineTransformation("testsource", null, CombineTransformation::COMBINE_TYPE_UNION_ALL);

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $sqlQuery = $this->processor->updateQuery($combineTransformation, new SQLQuery("*", "test_table"), [], $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.* FROM (SELECT * FROM test_table) T1 UNION ALL SELECT T2.* FROM (SELECT * FROM combine_table) T2) T3"),
            $sqlQuery);
    }

    public function testCanUpdateQuerySuccessfullyForIntersect() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasource->returnValue("buildQuery", new SQLQuery("*", "combine_table"), [
            []
        ]);
        $combineDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $combineDatasource->returnValue("getConfig", $combineDatasourceConfig);
        $combineDatasourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try simple non aliased columns
        $combineTransformation = new CombineTransformation("testsource", null, CombineTransformation::COMBINE_TYPE_INTERSECT);

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $sqlQuery = $this->processor->updateQuery($combineTransformation, new SQLQuery("*", "test_table"), [], $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.* FROM (SELECT * FROM test_table) T1) T4 INNER JOIN (SELECT T2.* FROM (SELECT * FROM combine_table) T2) T5 ON T4.fieldA = T5.fieldA AND T4.fieldB = T5.fieldB"),
            $sqlQuery);
    }

    public function testCanUpdateQuerySuccessfullyForExcept() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasource->returnValue("buildQuery", new SQLQuery("*", "combine_table"), [
            []
        ]);
        $combineDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $combineDatasource->returnValue("getConfig", $combineDatasourceConfig);
        $combineDatasourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try simple non aliased columns
        $combineTransformation = new CombineTransformation("testsource", null, CombineTransformation::COMBINE_TYPE_EXCEPT);

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $sqlQuery = $this->processor->updateQuery($combineTransformation, new SQLQuery("*", "test_table"), [], $mainDataSource);

        $this->assertEquals(new SQLQuery("*", "(SELECT T1.* FROM (SELECT * FROM test_table) T1 LEFT JOIN (SELECT * FROM combine_table) T2 ON T1.fieldA = T2.fieldA AND T1.fieldB = T2.fieldB WHERE T2.fieldA IS NULL AND T2.fieldB IS NULL) T3"),
            $sqlQuery);
    }

    public function testCanDoSimpleFieldMappings() {
        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasource->returnValue("buildQuery", new SQLQuery("*", "combine_table"), [
            []
        ]);
        $combineDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $combineDatasource->returnValue("getConfig", $combineDatasourceConfig);
        $combineDatasourceConfig->returnValue("getColumns", [new Field("fieldC"), new Field("fieldD")]);


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try simple non aliased columns
        $combineTransformation = new CombineTransformation("testsource", null, CombineTransformation::COMBINE_TYPE_UNION, ["fieldD" => new Field("fieldA"), "fieldC" => new Field("fieldB")]);

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $sqlQuery = $this->processor->updateQuery($combineTransformation, new SQLQuery("*", "test_table"), [], $mainDataSource);

        $this->assertEquals(new SQLQuery("*", "(SELECT T1.fieldA,T1.fieldB FROM (SELECT * FROM test_table) T1 UNION SELECT T2.fieldD,T2.fieldC FROM (SELECT * FROM combine_table) T2) T3"),
            $sqlQuery);
    }

    public function testCanUpdateQuerySuccessfullyForIntersectWithFieldMappings() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasource->returnValue("buildQuery", new SQLQuery("*", "combine_table"), [
            []
        ]);
        $combineDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $combineDatasource->returnValue("getConfig", $combineDatasourceConfig);
        $combineDatasourceConfig->returnValue("getColumns", [new Field("fieldC"), new Field("fieldD")]);


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try simple non aliased columns
        $combineTransformation = new CombineTransformation("testsource", null, CombineTransformation::COMBINE_TYPE_INTERSECT, ["fieldD" => new Field("fieldA"), "fieldC" => new Field("fieldB")]);

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $sqlQuery = $this->processor->updateQuery($combineTransformation, new SQLQuery("*", "test_table"), [], $mainDataSource);

        $this->assertEquals(new SQLQuery("*", "(SELECT T1.fieldA,T1.fieldB FROM (SELECT * FROM test_table) T1) T4 INNER JOIN (SELECT T2.fieldD,T2.fieldC FROM (SELECT * FROM combine_table) T2) T5 ON T4.fieldA = T5.fieldD AND T4.fieldB = T5.fieldC"),
            $sqlQuery);
    }

    public function testCanUpdateQuerySuccessfullyForExceptWithFieldMappings() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasource->returnValue("buildQuery", new SQLQuery("*", "combine_table"), [
            []
        ]);
        $combineDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $combineDatasource->returnValue("getConfig", $combineDatasourceConfig);
        $combineDatasourceConfig->returnValue("getColumns", [new Field("fieldC"), new Field("fieldD")]);


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try simple non aliased columns
        $combineTransformation = new CombineTransformation("testsource", null, CombineTransformation::COMBINE_TYPE_EXCEPT, ["fieldD" => new Field("fieldA"), "fieldC" => new Field("fieldB")]);

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $sqlQuery = $this->processor->updateQuery($combineTransformation, new SQLQuery("*", "test_table"), [], $mainDataSource);

        $this->assertEquals(new SQLQuery("*", "(SELECT T1.fieldA,T1.fieldB FROM (SELECT * FROM test_table) T1 LEFT JOIN (SELECT * FROM combine_table) T2 ON T1.fieldA = T2.fieldD AND T1.fieldB = T2.fieldC WHERE T2.fieldD IS NULL AND T2.fieldC IS NULL) T3"),
            $sqlQuery);
    }


    public function testDoesUpdateColumnsOfDataSourceCorrectly() {

        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);

        $combineDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $combineDatasourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $combineDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $combineDatasource->returnValue("buildQuery", new SQLQuery("*", "combine_table"), [
            []
        ]);
        $combineDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());
        $combineDatasource->returnValue("getConfig", $combineDatasourceConfig);
        $combineDatasourceConfig->returnValue("getColumns", [new Field("fieldC"), new Field("fieldD")]);


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("fieldA"), new Field("fieldB")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        $combineTransformation = new CombineTransformation("testsource", null, CombineTransformation::COMBINE_TYPE_EXCEPT, ["fieldD" => new Field("fieldA", "First Field"), "fieldC" => new Field("fieldB", "Second Field")]);

        $combineTransformation->setEvaluatedDataSource($combineDatasource);
        $this->processor->updateQuery($combineTransformation, new SQLQuery("*", "test_table"), [], $mainDataSource);


        $this->assertTrue($mainDataSourceConfig->methodWasCalled("setColumns", [[new Field("fieldA", "First Field"), new Field("fieldB", "Second Field")]]));
    }

}