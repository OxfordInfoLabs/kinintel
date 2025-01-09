<?php


namespace Kinintel\Services\Datasource;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Template\ValueFunction\ValueFunctionEvaluator;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\InvalidParametersException;
use Kinintel\Exception\MissingDatasourceUpdaterException;
use Kinintel\Exception\UnsupportedDatasourceTransformationException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\BaseUpdatableDatasource;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceUpdater;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Objects\Datasource\UpdatableTabularDatasource;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Hook\DatasourceHookService;
use Kinintel\Test\ValueObjects\Transformation\AnotherTestTransformation;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Application\DataSearchItem;
use Kinintel\ValueObjects\Dataset\DatasetTree;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\TabularResultsDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;
use Kinintel\ValueObjects\Datasource\WebService\JSONWebServiceDataSourceConfig;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
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
     * @var MockObject
     */
    private $datasourceHookService;

    /**
     * Set up
     */
    public function setUp(): void {
        $this->datasourceDAO = MockObjectProvider::instance()->getMockInstance(DatasourceDAO::class);
        $this->securityService = MockObjectProvider::instance()->getMockInstance(SecurityService::class);
        $this->valueFunctionEvaluator = MockObjectProvider::instance()->getMockInstance(ValueFunctionEvaluator::class);
        $this->datasourceHookService = MockObjectProvider::instance()->getMockInstance(DatasourceHookService::class);
        $this->dataSourceService = new DatasourceService($this->datasourceDAO, $this->securityService, $this->valueFunctionEvaluator,
            Container::instance()->get(DataProcessorService::class), $this->datasourceHookService);

    }


    public function testOnSaveOfDatasourceInstanceDAOIsCalledAndOnInstanceSaveIsCalledOnDatasource() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseUpdatableDatasource::class);
        $dataSourceInstance->returnValue("returnDataSource", $dataSource);


        $this->dataSourceService->saveDataSourceInstance($dataSourceInstance);

        // Check that the datasource was saved via the DAO and on save called
        $this->assertTrue($this->datasourceDAO->methodWasCalled("saveDataSourceInstance", [
            $dataSourceInstance
        ]));


    }

    public function testOnRemoveOfDatasourceInstanceDAOIsCalled() {


        $this->dataSourceService->removeDatasourceInstance("twinkle");

        // Check that the datasource was saved via the DAO and on save called
        $this->assertTrue($this->datasourceDAO->methodWasCalled("removeDatasourceInstance", [
            "twinkle"
        ]));


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

        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test"));


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


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test", [], [$transformationInstance1, $transformationInstance2, $transformationInstance3], 0, 20));


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
            $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test");
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
            $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test", ["param1" => "My Bad Type"]);
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


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test"));

    }


    public function testAccountIdParameterCorrectlyAddedIfLoggedInAccount() {

        $this->securityService->returnValue("getLoggedInSecurableAndAccount", [
            null, new Account("Test", 0, Account::STATUS_ACTIVE, 56)
        ]);

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
        $dataSource->returnValue("materialise", $dataSet, [[
            "param1" => "",
            "ACCOUNT_ID" => 56
        ]]);


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test"));


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


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test", ["param1" => "Joe", "param2" => "Bloggs"], [$transformationInstance1], 10, 10));


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


        $this->assertSame($pagedDataSet, $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test", [], [$transformationInstance1], 15, 50));


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
            $this->dataSourceService->getEvaluatedDataSourceByInstanceKey("test", [], [
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
        $this->dataSourceService->updateDatasourceInstanceByKey("test", new DatasourceUpdate());

        // Set explicit account
        $dataSourceInstance->returnValue("getAccountId", 1);

        // This should be fine as super user
        $this->dataSourceService->updateDatasourceInstanceByKey("test", new DatasourceUpdate());

        // Now login as regular user
        $this->securityService->returnValue("isSuperUserLoggedIn", false);

        $dataSourceInstance->returnValue("getAccountId", null);

        // This should fail
        try {
            $this->dataSourceService->updateDatasourceInstanceByKey("test", new DatasourceUpdate());
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
            $this->dataSourceService->updateDatasourceInstanceByKey("test", $dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);
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
        $dataSourceInstance->returnValue("getKey", "test");
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
        ],
            [
                ["name" => "Replace me", "age" => 88],
                ["name" => "Replace me twice", "age" => 65]
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

        $replaceDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Replace me", "age" => 88],
            ["name" => "Replace me twice", "age" => 65]
        ]);

        $this->dataSourceService->updateDatasourceInstanceByKey("test", $datasourceUpdate);

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $addDatasource, UpdatableDatasource::UPDATE_MODE_ADD
        ]));

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $updateDatasource, UpdatableDatasource::UPDATE_MODE_UPDATE
        ]));

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $deleteDatasource, UpdatableDatasource::UPDATE_MODE_DELETE
        ]));

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $replaceDatasource, UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));


    }


    public function testCanUpdateDatasourceTitleImportKeyAndFieldsIfUpdateWithStructureObjectPassedToUpdateMethod() {

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

        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", "hello-my-world", [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [], [
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

        $this->dataSourceService->updateDatasourceInstanceByKey("test", $datasourceUpdate);


        // Check title was updated
        $this->assertTrue($dataSourceInstance->methodWasCalled("setTitle", [
            "Hello world"
        ]));

        $this->assertTrue($dataSourceInstance->methodWasCalled("setImportKey", [
            "hello-my-world"
        ]));


        // Check column configuration was updated
        $this->assertTrue($dataSourceConfig->methodWasCalled("setColumns", [
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


    public function testCanDoFullUpdateForImportKeyProjectAndAccount() {

        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $dataSource->returnValue("getConfig", $dataSourceConfig);
        $this->datasourceDAO->returnValue("getDatasourceInstanceByImportKey", $dataSourceInstance, [
            "test-import",
            22
        ]);

        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", "hello-my-world", [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [], [
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

        $this->dataSourceService->updateDatasourceInstanceByImportKey("test-import", $datasourceUpdate, 22);


        // Check title was updated
        $this->assertTrue($dataSourceInstance->methodWasCalled("setTitle", [
            "Hello world"
        ]));

        $this->assertTrue($dataSourceInstance->methodWasCalled("setImportKey", [
            "hello-my-world"
        ]));


        // Check column configuration was updated
        $this->assertTrue($dataSourceConfig->methodWasCalled("setColumns", [
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


    public function testIdFieldsAreRemovedFromColumnListWhenAddingDataWithStructure() {


        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSourceConfig = new TabularResultsDatasourceConfig([]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $dataSource->returnValue("getConfig", $dataSourceConfig);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $datasourceUpdate = new DatasourceUpdateWithStructure("My Source", null, [
            new Field("id", "Id", null, Field::TYPE_ID),
            new Field("age")
        ], [], [
            ["id" => "Joe Bloggs", "age" => 12],
            ["id" => "Mary Jane", "age" => 7]
        ]);

        $addDatasource = new ArrayTabularDataset([
            new Field("age")
        ], [
            ["id" => "Joe Bloggs", "age" => 12],
            ["id" => "Mary Jane", "age" => 7]
        ]);


        $this->dataSourceService->updateDatasourceInstanceByKey("test", $datasourceUpdate);

        $this->assertTrue($dataSource->methodWasCalled("update", [
            $addDatasource, UpdatableDatasource::UPDATE_MODE_ADD
        ]));


    }

    public function testIndexesAreUpdatedCorrectlyOnDatasourceIfSuppliedInDataUpdateWithStructureForManagedTableDatasourceConfig() {

        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSourceConfig = MockObjectProvider::instance()->getMockInstance(ManagedTableSQLDatabaseDatasourceConfig::class);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $dataSource->returnValue("getConfig", $dataSourceConfig);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $datasourceUpdate = new DatasourceUpdateWithStructure("Indexed Source", null, [
            new Field("id", "Id", null, Field::TYPE_INTEGER),
            new Field("age")
        ], [
            new Index(["id"]),
            new Index(["age"]),
            new Index(["age", "id"])
        ], [
            ["id" => "Joe Bloggs", "age" => 12],
            ["id" => "Mary Jane", "age" => 7]
        ]);

        $this->dataSourceService->updateDatasourceInstanceByKey("test", $datasourceUpdate);

        // Check column configuration was updated
        $this->assertTrue($dataSourceConfig->methodWasCalled("setColumns", [
            [
                new Field("id", "Id", null, Field::TYPE_INTEGER),
                new Field("age")
            ]
        ]));

        $this->assertTrue($dataSourceConfig->methodWasCalled("setIndexes", [
            [
                new Index(["id"]),
                new Index(["age"]),
                new Index(["age", "id"])
            ]
        ]));

    }


    public function testCanCheckIfImportKeyIsInUseByAnotherDatasourceInstance() {

        $newOne = new DatasourceInstance("mynewone", "My New One", "test");
        $otherNewOne = new DatasourceInstance("myothernewone", "My Other New One", "test");

        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $newOne, ["mynewone"]);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $otherNewOne, ["myothernewone"]);

        $this->datasourceDAO->returnValue("importKeyAvailableForDatasourceInstance", true, [$newOne, "testimport"]);
        $this->datasourceDAO->returnValue("importKeyAvailableForDatasourceInstance", false, [$otherNewOne, "testimport"]);

        $this->assertTrue($this->dataSourceService->importKeyAvailableForDatasourceInstance("mynewone", "testimport"));
        $this->assertFalse($this->dataSourceService->importKeyAvailableForDatasourceInstance("myothernewone", "testimport"));

    }


    /**
     * @doesNotPerformAssertions
     */
    public function testAccessToDatasourceUpdateCheckedAgainstProjectPrivileges() {

        $testDs = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        Container::instance()->addInterfaceImplementation(Datasource::class, "test", get_class($testDs));

        $newOne = new DatasourceInstance("mynewone", "My New One", "test");
        $newOne->setAccountId(1);
        $newOne->setProjectKey("myproject");

        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $newOne, ["mynewone"]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", false, [
            Role::SCOPE_PROJECT, "customdatasourceupdate", "myproject"
        ]);

        $this->securityService->returnValue("checkLoggedInHasPrivilege", false, [
            Role::SCOPE_PROJECT, "customdatasourcemanage", "myproject"
        ]);


        try {
            $this->dataSourceService->updateDatasourceInstanceByKey("mynewone", new DatasourceUpdate([]));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }

        // Check we can update data but not structure with update privilege
        $this->securityService->returnValue("checkLoggedInHasPrivilege", true, [
            Role::SCOPE_PROJECT, "customdatasourceupdate", "myproject"
        ]);

        $this->dataSourceService->updateDatasourceInstanceByKey("mynewone", new DatasourceUpdate([]));

        try {
            $this->dataSourceService->updateDatasourceInstanceByKey("mynewone", new DatasourceUpdateWithStructure("hello"));
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
        }


        // Check we can update structure with update permission
        $this->securityService->returnValue("checkLoggedInHasPrivilege", true, [
            Role::SCOPE_PROJECT, "customdatasourcemanage", "myproject"
        ]);

        $this->dataSourceService->updateDatasourceInstanceByKey("mynewone", new DatasourceUpdateWithStructure("hello"));

    }

    public function testCanIssueFilteredDeleteForDatasourceInstanceByKey() {


        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSourceConfig = new TabularResultsDatasourceConfig([]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $dataSource->returnValue("getConfig", $dataSourceConfig);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $filterJunction = new FilterJunction([new Filter("[[name]]", "mark")]);
        $this->dataSourceService->filteredDeleteFromDatasourceInstanceByKey("test", $filterJunction);

        $this->assertTrue($dataSource->methodWasCalled("filteredDelete", [$filterJunction]));


    }


    public function testCanIssueFilteredDeleteForDatasourceInstanceByImportKey() {


        // Login as superuser
        $this->securityService->returnValue("isSuperUserLoggedIn", true);

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSourceConfig = new TabularResultsDatasourceConfig([]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $dataSource->returnValue("getConfig", $dataSourceConfig);
        $this->datasourceDAO->returnValue("getDatasourceInstanceByImportKey", $dataSourceInstance, [
            "test-import",
            22
        ]);

        $filterJunction = new FilterJunction([new Filter("[[name]]", "mark")]);
        $this->dataSourceService->filteredDeleteFromDatasourceInstanceByImportKey("test-import", $filterJunction, 22);

        $this->assertTrue($dataSource->methodWasCalled("filteredDelete", [$filterJunction]));


    }


    public function testAccountOwnedDatasourcesAreReturnedAsTreeCorrectly() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        /**
         * @var DatasourceService $datasourceService
         */
        $datasourceService = Container::instance()->get(DatasourceService::class);

        $config = new SQLDatabaseDatasourceConfig("table", "test_custom", "", [new Field("value", null, null, Field::TYPE_STRING, true)]);
        $accountDatasource = new DatasourceInstance("test-ds", "Test Datasource", "custom", $config, "sql");
        $accountDatasource->setAccountId(1);
        $accountDatasource->save();

        $accountDSItem = new DataSearchItem("custom", "test-ds", "Test Datasource", "", "Sam Davis Design");

        $tree = $datasourceService->getDatasetTreeForDatasourceKey("test-ds");
        $this->assertEquals(new DatasetTree($accountDSItem), $tree);


    }

    public function testSnapshotsAreTraversedCorrectlyWithBuildingDatasetsIncludedInTree() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        /**
         * @var DatasourceService $datasourceService
         */
        $datasourceService = Container::instance()->get(DatasourceService::class);

        $derivedDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Derived Dataset", "test"), 1);
        $derivedDatasetInstance->save();
        $derivedDatasetItem = new DataSearchItem("datasetinstance", $derivedDatasetInstance->getId(), "Derived Dataset", null, "Sam Davis Design");

        $snapshot = new DataProcessorInstance("test-snap", "Test Snapshot", "tabulardatasetsnapshot", [], null, null, "DatasetInstance", $derivedDatasetInstance->getId());
        $snapshot->setAccountId(1);
        $snapshot->save();

        $config = new SQLDatabaseDatasourceConfig("table", "test_snap_latest", "", [new Field("value", null, null, Field::TYPE_STRING, true)]);
        $snapshotDatasource = new DatasourceInstance("test-snap_latest", "Latest Snapshot", "snapshot", $config, "sql");
        $snapshotDatasource->setAccountId(1);
        $snapshotDatasource->save();

        $snapshotItem = new DataSearchItem("snapshot", "test-snap", "Test Snapshot", "", "Sam Davis Design");

        $tree = $datasourceService->getDatasetTreeForDatasourceKey("test-snap_latest");
        $this->assertEquals(new DatasetTree($snapshotItem, new DatasetTree($derivedDatasetItem)), $tree);

    }
}
