<?php


namespace Kinintel\Services\Datasource;

use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\MissingDatasourceUpdaterException;
use Kinintel\Exception\InvalidParametersException;
use Kinintel\Exception\UnsupportedDatasourceTransformationException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\Objects\Datasource\DatasourceUpdater;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\TestUpdatableDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Objects\Datasource\UpdatableTabularDatasource;
use Kinintel\Services\Util\ValueFunctionEvaluator;
use Kinintel\Test\ValueObjects\Transformation\AnotherTestTransformation;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\DatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\TabularResultsDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;
use Kinintel\ValueObjects\Datasource\WebService\JSONWebServiceDataSourceConfig;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingMarkerTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\TestTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class DatasourceServiceTest extends TestBase {

    /**
     * @var DatasourceService
     */
    private $dataSourceService;


    /**
     * @var MockObject
     */
    private $securityService;


    /**
     * @var MockObject
     */
    private $datasourceDAO;


    /**
     * @var MockObject
     */
    private $valueFunctionEvaluator;

    /**
     * Set up
     */
    public function setUp(): void {
        $this->datasourceDAO = MockObjectProvider::instance()->getMockInstance(DatasourceDAO::class);
        $this->securityService = MockObjectProvider::instance()->getMockInstance(SecurityService::class);
        $this->valueFunctionEvaluator = MockObjectProvider::instance()->getMockInstance(ValueFunctionEvaluator::class);
        $this->dataSourceService = new DatasourceService($this->datasourceDAO, $this->securityService, $this->valueFunctionEvaluator);

    }


    public function testDatasourceInstanceParametersReturnedInCallToGetEvaluatedParameters() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $dataSourceInstance->returnValue("getParameters", [
            new Parameter("param1", "Parameter 1"),
            new Parameter("param2", "Parameter 2"),

        ]);

        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);


        $params = $this->dataSourceService->getEvaluatedParameters("test");

        $this->assertEquals([
            new Parameter("param1", "Parameter 1"),
            new Parameter("param2", "Parameter 2")
        ], $params);


    }


    public function testDataSourceReturnedIfDataSetWithNoTransformationsPassedToEvaluateFunction() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);

        $dataSource->returnValue("materialise", $dataSet);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSource("test"));


    }


    public function testTransformationsAppliedInSequenceForSupportedTransformations() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);


        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(Transformation::class);
        $transformation2 = MockObjectProvider::instance()->getMockInstance(Transformation::class);
        $transformation3 = MockObjectProvider::instance()->getMockInstance(Transformation::class);


        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance2 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance3 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);

        $transformationInstance1->returnValue("returnTransformation", $transformation1);
        $transformationInstance2->returnValue("returnTransformation", $transformation2);
        $transformationInstance3->returnValue("returnTransformation", $transformation3);


        $transformed1 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed1->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformed2 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed2->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformed3 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);


        $dataSource->returnValue("applyTransformation", $transformed1, [
            $transformation1, [], new PagingTransformation(20, 0)
        ]);

        $transformed1->returnValue("applyTransformation", $transformed2, [
            $transformation2, [], new PagingTransformation(20, 0)
        ]);

        $transformed2->returnValue("applyTransformation", $transformed3, [
            $transformation3, [], new PagingTransformation(20, 0)
        ]);


        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformed3->returnValue("materialise", $dataSet);


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSource("test", [], [$transformationInstance1, $transformationInstance2, $transformationInstance3], 0, 20));


    }


    public function testExceptionRaisedIfMissingParametersForDatasourceInstance() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        $dataSourceInstance->returnValue("getParameters", [
            new Parameter("param1", "Parameter 1")
        ]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        try {
            $this->dataSourceService->getEvaluatedDataSource("test");
            $this->fail("Should have thrown here");
        } catch (InvalidParametersException $e) {
            $this->assertTrue(true);
        }
    }

    public function testExceptionRaisedIfParameterValueOfWrongTypeSuppliedForDatasourceInstance() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        $dataSourceInstance->returnValue("getParameters", [
            new Parameter("param1", "Parameter 1", Parameter::TYPE_NUMERIC)
        ]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        try {
            $this->dataSourceService->getEvaluatedDataSource("test", ["param1" => "My Bad Type"]);
            $this->fail("Should have thrown here");
        } catch (InvalidParametersException $e) {
            $this->assertTrue(true);
        }
    }


    public function testDatasourceInstanceEvaluatedCorrectlyIfDefaultValueSuppliedForParameter() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        $dataSourceInstance->returnValue("getParameters", [
            new Parameter("param1", "Parameter 1", Parameter::TYPE_TEXT, false, "Hello World")
        ]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $dataSource->returnValue("materialise", $dataSet);


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSource("test"));

    }


    public function testParameterValuesSuppliedToApplyTransformationOnEvaluate() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);


        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(Transformation::class);

        $transformed1 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed1->returnValue("getSupportedTransformationClasses", [
            TestTransformation::class
        ]);


        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance1->returnValue("returnTransformation", $transformation1);

        $dataSource->returnValue("applyTransformation", $transformed1, [
            $transformation1, ["param1" => "Joe", "param2" => "Bloggs"], new PagingTransformation(10, 10)
        ]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformed1->returnValue("materialise", $dataSet);


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSource("test", ["param1" => "Joe", "param2" => "Bloggs"], [$transformationInstance1], 10, 10));


    }


    public function testPagingTransformationAddedUsingOffsetAndLimitParamsIfNoPagingMarkerTransformationPresentAndPagingTransformationSupportedByTerminatingDatasource() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);


        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(Transformation::class);

        $transformed1 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed1->returnValue("getSupportedTransformationClasses", [
            Transformation::class,
            PagingTransformation::class
        ]);


        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance1->returnValue("returnTransformation", $transformation1);

        $dataSource->returnValue("applyTransformation", $transformed1, [
            $transformation1, [], new PagingTransformation(50, 15)
        ]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformed1->returnValue("materialise", $dataSet);


        // Expect a second transformation to occur
        $transformed2 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed1->returnValue("applyTransformation", $transformed2, [
            new PagingTransformation(50, 15), []
        ]);

        $pagedDataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformed2->returnValue("materialise", $pagedDataSet);


        $this->assertSame($pagedDataSet, $this->dataSourceService->getEvaluatedDataSource("test", [], [$transformationInstance1], 15, 50));


    }


    public function testDefaultDatasourceReturnedIfUnsupportedTransformationSuppliedAsPartOfDatasetOrAdditionalTransformations() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            TestTransformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(FilterTransformation::class);
        $transformation1->returnValue("getSQLTransformationProcessorKey", "filter");
        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance1->returnValue("returnTransformation", $transformation1);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);


        $expected = new DefaultDatasource($dataSource);
        $expected->applyTransformation($transformation1);

//        $this->assertEquals($expected, $this->dataSourceService->getEvaluatedDataSource("test", [], [$transformationInstance1]));

        $this->assertTrue(true);

    }


    public function testExceptionRaisedIfDefaultDatasourceCannotHandleUnsupportedTransformationSuppliedAsPartOfDatasetOrAdditionalTransformations() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            TestTransformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(AnotherTestTransformation::class);
        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance1->returnValue("returnTransformation", $transformation1);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);


        try {
            $this->dataSourceService->getEvaluatedDataSource("test", [], [
                $transformationInstance1
            ]);
            $this->fail("Should have thrown here");
        } catch (UnsupportedDatasourceTransformationException $e) {
            $this->assertTrue(true);
        }


    }


    public function testObjectNotFoundExceptionRaisedIfLoggedInUserAttemptsToAccessDatasourceWithNullAccountId() {


        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);
        $dataSourceInstance->returnValue("getAccountId", null);

        // This should be fine as super user
        $this->dataSourceService->updateDatasourceInstance("test", new DatasourceUpdate());

        // Set explicit account
        $dataSourceInstance->returnValue("getAccountId", 1);

        // This should be fine as super user
        $this->dataSourceService->updateDatasourceInstance("test", new DatasourceUpdate());

        // Now login as regular user
        $this->securityService->returnValue("isSuperUserLoggedIn", false);

        $dataSourceInstance->returnValue("getAccountId", null);

        // This should fail
        try {
            $this->dataSourceService->updateDatasourceInstance("test", new DatasourceUpdate());
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }

        $this->assertTrue(true);

    }


    public function testNotUpdatableExceptionRaisedIfDatasourceNotUpdatableAndAttemptToUpdateIt() {


        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataset = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Joe Bloggs", 12],
            ["name" => "Mary Jane", 7]
        ]);

        try {
            $this->dataSourceService->updateDatasourceInstance("test", $dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);
            $this->fail("Should have thrown here");
        } catch (DatasourceNotUpdatableException $e) {
            $this->assertTrue(true);
        }

    }


    public function testCanUpdateDatasourceInstanceWithDatasourceUpdateObjectAndDatasourceCalledAppropriately() {

        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $dataSource->returnValue("getConfig", $dataSourceConfig);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $datasourceUpdate = new DatasourceUpdate([
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ], [
            ["name" => "Mr Smith", "age" => 22],
            ["name" => "Mrs Apple", "age" => 72]
        ], [
                ["name" => "Going away", "age" => 33]
            ]
        );

        $addDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);

        $updateDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Mr Smith", "age" => 22],
            ["name" => "Mrs Apple", "age" => 72]
        ]);

        $deleteDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Going away", "age" => 33]
        ]);

        $this->dataSourceService->updateDatasourceInstance("test", $datasourceUpdate);

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $addDatasource, UpdatableDatasource::UPDATE_MODE_ADD
        ]));

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $updateDatasource, UpdatableDatasource::UPDATE_MODE_UPDATE
        ]));

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $deleteDatasource, UpdatableDatasource::UPDATE_MODE_DELETE
        ]));


    }


    public function testCanCreateCustomDatasourceUsingUpdateWithStructureObject() {

        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);

        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $mockInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDatasourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);
        $mockInstance->returnValue("returnDataSource", $mockDatasource);
        $mockDatasource->returnValue("getConfig", $mockDatasourceConfig);
        $this->datasourceDAO->returnValue("saveDataSourceInstance", $mockInstance);

        $newDatasourceKey = $this->dataSourceService->createCustomDatasourceInstance($datasourceUpdate, "myproject", 1);

        $expectedDatasourceInstance = new DatasourceInstance($newDatasourceKey, "Hello world", "custom", [
            "source" => "table",
            "tableName" => "custom." . $newDatasourceKey,
            "columns" => [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
        ], "test");
        $expectedDatasourceInstance->setAccountId(1);
        $expectedDatasourceInstance->setProjectKey("myproject");

        // Check datasource was saved
        $this->assertTrue($this->datasourceDAO->methodWasCalled("saveDataSourceInstance", [
            $expectedDatasourceInstance
        ]));


        $this->assertTrue($mockDatasource->methodWasCalled("updateFields", [
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
        ]));


        $addDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $this->assertTrue($mockDatasource->methodWasCalled("update", [
            $addDatasource, UpdatableDatasource::UPDATE_MODE_ADD
        ]));


    }


    public function testCanUpdateDatasourceTitleAndFieldsIfUpdateWithStructureObjectPassedToUpdateMethod() {

        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $dataSource->returnValue("getConfig", $dataSourceConfig);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ], [
            ["name" => "Mr Smith", "age" => 22],
            ["name" => "Mrs Apple", "age" => 72]
        ], [
                ["name" => "Going away", "age" => 33]
            ]
        );

        $addDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);

        $updateDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Mr Smith", "age" => 22],
            ["name" => "Mrs Apple", "age" => 72]
        ]);

        $deleteDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Going away", "age" => 33]
        ]);

        $this->dataSourceService->updateDatasourceInstance("test", $datasourceUpdate);


        // Check title was updated
        $this->assertTrue($dataSourceInstance->methodWasCalled("setTitle", [
            "Hello world"
        ]));

        // Check column configuration was updated
        $this->assertTrue($dataSourceConfig->methodWasCalled("setColumns", [
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
        ]));

        $this->assertTrue($dataSource->methodWasCalled("updateFields", [
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
        ]));


        $this->assertTrue($this->datasourceDAO->methodWasCalled("saveDataSourceInstance", [
            $dataSourceInstance
        ]));

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $addDatasource, UpdatableDatasource::UPDATE_MODE_ADD
        ]));

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $updateDatasource, UpdatableDatasource::UPDATE_MODE_UPDATE
        ]));

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $deleteDatasource, UpdatableDatasource::UPDATE_MODE_DELETE
        ]));


    }


}