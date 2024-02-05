<?php

namespace Kinintel\Test\Objects\Datasource;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\ExtendingDatasource;
use Kinintel\Objects\Datasource\TestDatasource;
use Kinintel\Objects\Datasource\WebService\WebServiceDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\Configuration\ExtendingDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class ExtendingDatasourceTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $datasourceService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
    }

    public function testExtendingSourceWithConfiguredBaseDatasourceIsMaterialisedCorrectly() {

        $baseInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $baseDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $baseInstance->returnValue("returnDataSource", $baseDatasource);
        $baseDatasource->returnValue("materialise", "BINGO BANGO", [[]]);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            $baseInstance, ["base"]);

        $config = new ExtendingDatasourceConfig("base");
        $extendingDatasource = new ExtendingDatasource($config);
        $extendingDatasource->setDatasourceService($this->datasourceService);

        $this->assertEquals("BINGO BANGO", $extendingDatasource->materialise([]));

    }

    public function testParametersArePassedThroughCorrectlyToBaseDataSourceOnMaterialise() {

        $baseInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $baseDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $baseInstance->returnValue("returnDataSource", $baseDatasource);
        $baseDatasource->returnValue("materialise", "BINGO BANGO", [["A" => "Yes", "B" => "No"]]);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            $baseInstance, ["base"]);

        $config = new ExtendingDatasourceConfig("base");
        $extendingDatasource = new ExtendingDatasource($config);
        $extendingDatasource->setDatasourceService($this->datasourceService);

        $this->assertEquals("BINGO BANGO", $extendingDatasource->materialise(["A" => "Yes", "B" => "No"]));

    }


    public function testTransformationInstancesAreAppliedToBaseDataSourceRecursivelyIfSupplied() {

        $baseInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $baseDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $baseInstance->returnValue("returnDataSource", $baseDatasource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            $baseInstance, ["base"]);

        $firstTransformationInstance = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $firstTransformation = new FilterTransformation([new Filter("test", "test")]);
        $firstTransformationInstance->returnValue("returnTransformation", $firstTransformation);
        $transformedDatasource1 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $baseDatasource->returnValue("applyTransformation", $transformedDatasource1, [$firstTransformation, ["A" => "Yes", "B" => "No"]]);

        $secondTransformationInstance = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $secondTransformation = new FilterTransformation([new Filter("other", "other")]);
        $secondTransformationInstance->returnValue("returnTransformation", $secondTransformation);
        $transformedDatasource2 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $transformedDatasource1->returnValue("applyTransformation", $transformedDatasource2, [$secondTransformation, ["A" => "Yes", "B" => "No"]]);

        $transformedDatasource2->returnValue("materialise", "BINGO BANGO", [["A" => "Yes", "B" => "No"]]);

        $config = new ExtendingDatasourceConfig("base", [
            $firstTransformationInstance, $secondTransformationInstance
        ]);
        $extendingDatasource = new ExtendingDatasource($config);
        $extendingDatasource->setDatasourceService($this->datasourceService);

        $this->assertEquals("BINGO BANGO", $extendingDatasource->materialise(["A" => "Yes", "B" => "No"]));


    }


    public function testCanApplyTransformationsToAnExtendingDataSourceAndTheseAreAppliedAfterwards() {

        $baseInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $baseDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $baseInstance->returnValue("returnDataSource", $baseDatasource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            $baseInstance, ["base"]);

        $firstTransformationInstance = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $firstTransformation = new FilterTransformation([new Filter("test", "test")]);
        $firstTransformationInstance->returnValue("returnTransformation", $firstTransformation);
        $transformedDatasource1 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $baseDatasource->returnValue("applyTransformation", $transformedDatasource1, [$firstTransformation, ["A" => "Yes", "B" => "No"]]);

        $secondTransformationInstance = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $secondTransformation = new FilterTransformation([new Filter("other", "other")]);
        $secondTransformationInstance->returnValue("returnTransformation", $secondTransformation);
        $transformedDatasource2 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $transformedDatasource1->returnValue("applyTransformation", $transformedDatasource2, [$secondTransformation, ["A" => "Yes", "B" => "No"]]);

        $thirdTransformationInstance = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $thirdTransformation = new FilterTransformation([new Filter("final", "final")]);
        $thirdTransformationInstance->returnValue("returnTransformation", $thirdTransformation);
        $transformedDatasource3 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $transformedDatasource2->returnValue("applyTransformation", $transformedDatasource3, [$thirdTransformation, ["A" => "Yes", "B" => "No"]]);

        $transformedDatasource3->returnValue("materialise", "BINGO BANGO", [["A" => "Yes", "B" => "No"]]);

        $config = new ExtendingDatasourceConfig("base", [
            $firstTransformationInstance, $secondTransformationInstance
        ]);
        $extendingDatasource = new ExtendingDatasource($config);
        $extendingDatasource->setDatasourceService($this->datasourceService);
        $extendingDatasource->applyTransformation($thirdTransformation, ["A" => "Yes", "B" => "No"]);

        $this->assertEquals("BINGO BANGO", $extendingDatasource->materialise(["A" => "Yes", "B" => "No"]));


    }

}