<?php

namespace Kinintel\Test\Services\Dataset;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Application\Activity;
use Kiniauth\Objects\Security\User;
use Kiniauth\Services\Application\ActivityLogger;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Dataset\DatasetServiceInterceptor;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class DatasetServiceInterceptorTest extends TestBase {

    /**
     * @var DatasetServiceInterceptor
     */
    private $interceptor;

    /**
     * @var MockObject
     */
    private $activeRecordInterceptor;

    /**
     * @var MockObject
     */
    private $activityLogger;


    /**
     * @var MockObject
     */
    private $datasetService;

    public function setUp(): void {
        $this->activeRecordInterceptor = MockObjectProvider::instance()->getMockInstance(ActiveRecordInterceptor::class);
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->activityLogger = MockObjectProvider::instance()->getMockInstance(ActivityLogger::class);
        $this->interceptor = new DatasetServiceInterceptor($this->activeRecordInterceptor, $this->activityLogger);
    }

    public function testCallableCalledWithNoOtherChangesIfMethodCallableCalledForNonInterceptingMethod() {

        $callable = function () {
            return "HELLO";
        };

        $result = $this->interceptor->methodCallable($callable, $this->datasetService, "getDatasetInstance", [
            23
        ], null);

        // Ensure result returned correctly
        $this->assertEquals("HELLO", $result());

        $this->assertFalse($this->activeRecordInterceptor->methodWasCalled("executeWithWhitelistedAccountReadAccess"));
    }


    public function testWhitelistingEnabledForAccountOnActiveRecordInterceptorIfMethodCallableCalledForEvaluateDataSetMethodWithFullDatasetInstance() {

        $callable = function () {
            return "HELLO";
        };

        $datasetInstance = new DatasetInstance(null, 25);
        $datasetInstance->setId(55);

        $result = $this->interceptor->methodCallable($callable, $this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
            "dataSetInstance" => $datasetInstance
        ], null);


        $result();

        // Ensure whitelisting called correctly
        $this->assertTrue($this->activeRecordInterceptor->methodWasCalled("executeWithWhitelistedAccountReadAccess", [
            $callable, 25
        ]));

    }

    public function testWhitelistingEnabledForAccountOnActiveRecordInterceptorIfMethodCallableCalledForGetTransformedDatasetMethodWithFullDatasetInstance() {

        $callable = function () {
            return "HELLO";
        };

        $datasetInstance = new DatasetInstance(null, 25);
        $datasetInstance->setId(55);

        $result = $this->interceptor->methodCallable($callable, $this->datasetService, "getTransformedDatasourceForDataSetInstance", [
            "dataSetInstance" => $datasetInstance
        ], null);


        $result();

        // Ensure whitelisting called correctly
        $this->assertTrue($this->activeRecordInterceptor->methodWasCalled("executeWithWhitelistedAccountReadAccess", [
            $callable, 25
        ]));

    }


    public function testWhitelistingEnabledForAccountOnActiveRecordInterceptorIfMethodCallableCalledForGetEvaluatedParametersMethodWithFullDatasetInstance() {

        $callable = function () {
            return "HELLO";
        };

        $datasetInstance = new DatasetInstance(null, 25);
        $datasetInstance->setId(55);

        $result = $this->interceptor->methodCallable($callable, $this->datasetService, "getEvaluatedParameters", [
            "dataSetInstance" => $datasetInstance
        ], null);


        $result();

        // Ensure whitelisting called correctly
        $this->assertTrue($this->activeRecordInterceptor->methodWasCalled("executeWithWhitelistedAccountReadAccess", [
            $callable, 25
        ]));

    }


    public function testDatasetInstanceSummaryObjectsAreResolvedToFullDatasetInstancesIfSupplied() {

        $callable = function () {
            return "HELLO";
        };

        // Programme return values
        $datasetInstanceSummary = new DatasetInstanceSummary("test", null, null, [new TransformationInstance("filter", new FilterTransformation())], [new Parameter("test", "Test")], ["test" => "hello"], null, null, null, 45);

        $datasetInstance = new DatasetInstance(null, 25);
        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [45]);

        $result = $this->interceptor->methodCallable($callable, $this->datasetService, "getTransformedDatasourceForDataSetInstance", [
            "dataSetInstance" => $datasetInstanceSummary
        ], null);


        $result();

        // Ensure whitelisting called correctly
        $this->assertTrue($this->activeRecordInterceptor->methodWasCalled("executeWithWhitelistedAccountReadAccess", [
            $callable, 25
        ]));

    }


//    public function testActivityLoggedCorrectlyForSimpleSuccessfulDatasetInstancesOnCompletion() {
//
//        $datasetInstanceSummary = new DatasetInstanceSummary("Test Query", "test-json");
//        $datasetInstance = new DatasetInstance($datasetInstanceSummary, 25);
//        $datasetInstance->setId(33);
//
//        // Call before method to generate transaction id
//        $this->interceptor->beforeMethod($this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
//            "dataSetInstance" => $datasetInstance
//        ], null);
//
//        $this->interceptor->afterMethod($this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
//            "dataSetInstance" => $datasetInstance
//        ], null, null);
//
//        $this->assertTrue($this->activityLogger->methodWasCalled("createLog", [
//            "Dataset Query",
//            33, "Test Query",
//            ["result" => "Success"],
//            date("U")
//        ]));
//
//    }
//
//
//    public function testActivityLoggedCorrectlyForSimpleFailedDatasetInstancesOnCompletion() {
//
//        $datasetInstanceSummary = new DatasetInstanceSummary("Test Query", "test-json");
//        $datasetInstance = new DatasetInstance($datasetInstanceSummary, 25);
//        $datasetInstance->setId(33);
//
//        // Call before method to generate transaction id
//        $this->interceptor->beforeMethod($this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
//            "dataSetInstance" => $datasetInstance
//        ], null);
//
//
//        $this->interceptor->onException($this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
//            "dataSetInstance" => $datasetInstance
//        ], new \Exception("Query Failed"), null);
//
//        $this->assertTrue($this->activityLogger->methodWasCalled("createLog", [
//            "Dataset Query",
//            33, "Test Query",
//            ["result" => "Error", "errorMessage" => "Query Failed"],
//            date("U")
//        ]));
//
//    }
//
//
//    public function testActivityLoggedOncePerAccountDatasourceLevelOnEvaluations() {
//
//        // Two from same account with hierarchy
//        $datasetInstanceSummary1 = new DatasetInstanceSummary("Test Query", "test-json");
//        $datasetInstance1 = new DatasetInstance($datasetInstanceSummary1, 25);
//        $datasetInstance1->setId(33);
//
//        $datasetInstanceSummary2 = new DatasetInstanceSummary("Test Derived Query", null, 33);
//        $datasetInstance2 = new DatasetInstance($datasetInstanceSummary2, 25);
//        $datasetInstance2->setId(35);
//
//        // Call methods in expected order
//        $this->interceptor->beforeMethod($this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
//            "dataSetInstance" => $datasetInstance2
//        ], null);
//
//        $this->interceptor->beforeMethod($this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
//            "dataSetInstance" => $datasetInstance1
//        ], null);
//
//
//        $this->interceptor->afterMethod($this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
//            "dataSetInstance" => $datasetInstance1
//        ], null, null);
//
//
//        $this->interceptor->afterMethod($this->datasetService, "getEvaluatedDataSetForDataSetInstance", [
//            "dataSetInstance" => $datasetInstance2
//        ], null, null);
//
//
//        // Check log created for derived query
//        $this->assertTrue($this->activityLogger->methodWasCalled("createLog", [
//            "Dataset Query",
//            35, "Test Derived Query",
//            ["result" => "Success"],
//            date("U")
//        ]));
//
//        // Check no log created for parent query
//        $this->assertFalse($this->activityLogger->methodWasCalled("createLog", [
//            "Dataset Query",
//            33, "Test Query",
//            ["result" => "Success"],
//            date("U")
//        ]));
//    }


}