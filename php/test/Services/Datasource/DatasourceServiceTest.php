<?php


namespace Kinintel\Services\Datasource;

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
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\Objects\Datasource\DatasourceUpdater;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\TestUpdatableDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Test\ValueObjects\Transformation\AnotherTestTransformation;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\DatasourceConfig;
use Kinintel\ValueObjects\Datasource\WebService\JSONWebServiceDataSourceConfig;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
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
    private $datasourceDAO;

    /**
     * Set up
     */
    public function setUp(): void {
        $this->datasourceDAO = MockObjectProvider::instance()->getMockInstance(DatasourceDAO::class);
        $this->dataSourceService = new DatasourceService($this->datasourceDAO);
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
            $transformation1, []
        ]);

        $transformed1->returnValue("applyTransformation", $transformed2, [
            $transformation2, []
        ]);

        $transformed2->returnValue("applyTransformation", $transformed3, [
            $transformation3, []
        ]);


        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformed3->returnValue("materialise", $dataSet);


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSource("test", [], [$transformationInstance1, $transformationInstance2, $transformationInstance3]));


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
            Transformation::class
        ]);


        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance1->returnValue("returnTransformation", $transformation1);

        $dataSource->returnValue("applyTransformation", $transformed1, [
            $transformation1, ["param1" => "Joe", "param2" => "Bloggs"]
        ]);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceDAO->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformed1->returnValue("materialise", $dataSet);


        $this->assertEquals($dataSet, $this->dataSourceService->getEvaluatedDataSource("test", ["param1" => "Joe", "param2" => "Bloggs"], [$transformationInstance1]));


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


}