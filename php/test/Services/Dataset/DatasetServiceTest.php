<?php

namespace Kinintel\Services\Dataset;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinintel\Exception\InvalidDatasourceTypeException;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Exception\InvalidTransformationTypeException;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Transformation\Query\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Query\FilterQuery;
use Kinintel\ValueObjects\Transformation\TestTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class DatasetServiceTest extends TestBase {

    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var DatasetService
     */
    private $datasetService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->datasetService = new DatasetService($this->datasourceService);
    }


    public function testDataSourceReturnedIfDataSetWithNoTransformationsPassedToEvaluateFunction() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSetInstance = new DatasetInstance("Test Dataset", "test");
        $this->assertEquals($dataSource, $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance));


    }


    public function testTransformationsAppliedInSequenceIfDataSetWithTransformationsPassedToEvaluateFunction() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);


        $transformation1 = MockObjectProvider::instance()->getMockInstance(Transformation::class);
        $transformation2 = MockObjectProvider::instance()->getMockInstance(Transformation::class);
        $transformation3 = MockObjectProvider::instance()->getMockInstance(Transformation::class);


        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance2 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance3 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);

        $transformationInstance1->returnValue("returnTransformation", $transformation1);
        $transformationInstance2->returnValue("returnTransformation", $transformation2);
        $transformationInstance3->returnValue("returnTransformation", $transformation3);


        $transformed1 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $transformed2 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $transformed3 = MockObjectProvider::instance()->getMockInstance(Datasource::class);


        $dataSource->returnValue("applyTransformation", $transformed1, [
            $transformation1
        ]);

        $transformed1->returnValue("applyTransformation", $transformed2, [
            $transformation2
        ]);

        $transformed2->returnValue("applyTransformation", $transformed3, [
            $transformation3
        ]);


        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSetInstance = new DatasetInstance("Test Dataset", "test", [
            $transformationInstance1, $transformationInstance2, $transformationInstance3
        ]);

        $this->assertEquals($transformed3, $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance));


    }


    public function testDataSourceAndTransformationsAreValidatedOnDataSetSave() {

        $dataSetInstance = new DatasetInstance("Test Dataset", "badsource");

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["datasourceInstanceKey"]));
        }


        $dataSetInstance = new DatasetInstance("Test Dataset", "test-json", [
            new TransformationInstance("badtrans")
        ]);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["transformationInstances"][0]["type"]));
        }


        $dataSetInstance = new DatasetInstance("Test Dataset", "test-json", [
            new TransformationInstance("Kinintel\ValueObjects\Transformation\TestTransformation", new TestTransformation())
        ]);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["transformationInstances"][0]["config"]["property"]));
        }

    }


    public function testCanSaveAndRetrieveValidDataSetInstances() {

        $dataSetInstance = new DatasetInstance("Test Dataset", "test-json", [
            new TransformationInstance("filterquery", new FilterQuery([
                new Filter("property", "foobar")
            ]))
        ]);

        $this->datasetService->saveDataSetInstance($dataSetInstance);

        $reSet = $this->datasetService->getDataSetInstance($dataSetInstance->getId());
        $this->assertEquals("Test Dataset", $reSet->getTitle());
        $this->assertEquals("test-json", $reSet->getDatasourceInstanceKey());
        $transformationInstance = $reSet->getTransformationInstances()[0];
        $this->assertEquals(new TransformationInstance("filterquery",
            [
                "filters" => [["fieldName" => "property",
                    "value" => "foobar",
                    "filterType" => "eq"]],
                "logic" => "and",
                "filterJunctions" => []
            ]
        ), $transformationInstance);

        // Check unserialisation works for transformation instance
        $this->assertEquals(new FilterQuery([
            new Filter("property", "foobar")
        ]), $transformationInstance->returnTransformation());

    }

}