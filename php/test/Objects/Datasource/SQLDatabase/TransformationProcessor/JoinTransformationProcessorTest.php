<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;

include_once "autoloader.php";

class JoinTransformationProcessorTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $dataSourceService;

    /**
     * @var JoinTransformationProcessor
     */
    private $processor;


    public function setUp(): void {
        $this->dataSourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->processor = new JoinTransformationProcessor($this->dataSourceService);
    }


    public function testIfTwoDatasourcesUseSameAuthenticationCredsSQLJoinCanBePerformedForSimpleTableJoin() {

        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", ":otherName", Filter::FILTER_TYPE_EQUALS)]));

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
        $mainDataSource->returnValue("buildQuery", new SQLQuery("*", "test_table"), [
            []
        ]);

        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("T1.*,T2.*", "(SELECT * FROM test_table) T1 INNER JOIN (SELECT * FROM join_table) T2 ON T2.name = T1.otherName"),
            $sqlQuery);


    }


}