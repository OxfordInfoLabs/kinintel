<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\Request\MockPHPInputStream;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Join\JoinColumn;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class JoinTransformationProcessorTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $dataSourceService;

    /**
     * @var MockObject
     */
    private $dataSetService;

    /**
     * @var JoinTransformationProcessor
     */
    private $processor;


    public function setUp(): void {
        $this->dataSourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->dataSetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->processor = new JoinTransformationProcessor($this->dataSourceService, $this->dataSetService);
    }


    public function testCanJoinToDatasourceUsingSameAuthenticationCredsAndSQLJoinQueryIsCreatedForColumnAndValueBasedJoins() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $joinDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $joinDatasourceInstance->returnValue("returnDataSource", $joinDatasource);

        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", new SQLQuery("*", "join_table"), [
            []
        ]);

        // Return the data source by key
        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $joinDatasourceInstance, ["testsource"]);

        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);


        // Try a simple column based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", "[[otherName]]", Filter::FILTER_TYPE_EQUALS)]));


        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("T1.*,T2.*", "(SELECT * FROM test_table) T1 INNER JOIN (SELECT * FROM join_table) T2 ON T2.name = T1.otherName"),
            $sqlQuery);


        // Try a simple value based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", "bobby", Filter::FILTER_TYPE_EQUALS)]));


        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("T3.*,T4.*", "(SELECT * FROM test_table) T3 INNER JOIN (SELECT * FROM join_table) T4 ON T4.name = ?", [
            "bobby"
        ]),
            $sqlQuery);


    }


    public function testExistingQueryParametersAreMergedIntoParametersForDatasourceJoinQuery() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $joinDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $joinDatasourceInstance->returnValue("returnDataSource", $joinDatasource);

        $joinQuery = new SQLQuery("*", "join_table");
        $joinQuery->setWhereClause("category = ? AND published = ?", ["swimming", 1]);

        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", $joinQuery, [
            []
        ]);

        // Return the data source by key
        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $joinDatasourceInstance, ["testsource"]);

        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);


        // Try a simple column based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", "*bob*", Filter::FILTER_TYPE_LIKE)]));


        $query = new SQLQuery("*", "test_table");
        $query->setWhereClause("archived = ?", [0]);


        $sqlQuery = $this->processor->updateQuery($joinTransformation, $query, [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("T1.*,T2.*", "(SELECT * FROM test_table WHERE archived = ?) T1 INNER JOIN (SELECT * FROM join_table WHERE category = ? AND published = ?) T2 ON T2.name LIKE ?",
            [
                0, "swimming", 1, "%bob%"
            ]),
            $sqlQuery);


    }


    public function testCanJoinToDatasetUsingSameAuthenticationCredsAndSQLJoinQueryIsCreatedForColumnAndValueBasedJoins() {


        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);

        // Set up and programme return of data set instance
        $joinDataSetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstanceSummary::class);
        $joinDataSetInstance->returnValue("getDatasourceInstanceKey", "testDS");
        $joinDataSetInstance->returnValue("getTransformationInstances", [
            new TransformationInstance("test"), new TransformationInstance("otherone")
        ]);
        $this->dataSetService->returnValue("getDataSetInstance", $joinDataSetInstance, [5]);

        // Ensure joined datasource returns this set of credentials
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);

        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", new SQLQuery("*", "join_table"), [
            []
        ]);

        // Return the data source by key
        $this->dataSourceService->returnValue("getTransformedDataSource", $joinDatasource, ["testDS", [
            new TransformationInstance("test"), new TransformationInstance("otherone")
        ], []]);

        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);


        // Try a simple column based join
        $joinTransformation = new JoinTransformation(null, 5, [],
            new FilterJunction([
                new Filter("name", "[[otherName]]", Filter::FILTER_TYPE_EQUALS)]));


        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("T1.*,T2.*", "(SELECT * FROM test_table) T1 INNER JOIN (SELECT * FROM join_table) T2 ON T2.name = T1.otherName"),
            $sqlQuery);


    }


    public function testIfJoinColumnsSuppliedToAJoinTransformationTheseAreSelectedExplicitlyFromJoinedTableWithAliases() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $joinDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $joinDatasourceInstance->returnValue("returnDataSource", $joinDatasource);

        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", new SQLQuery("*", "join_table"), [
            []
        ]);

        // Return the data source by key
        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $joinDatasourceInstance, ["testsource"]);

        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);


        // Try simple non aliased columns
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", "[[otherName]]", Filter::FILTER_TYPE_EQUALS)]), [
                new Field("name"), new Field("category"), new Field("status")
            ]);


        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("T1.*,T2.*", "(SELECT * FROM test_table) T1 INNER JOIN (SELECT name alias_1,category alias_2,status alias_3 FROM join_table) T2 ON T2.name = T1.otherName"),
            $sqlQuery);


    }


}