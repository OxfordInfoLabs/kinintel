<?php

namespace Kinintel\Test\Objects\Datasource;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\ExtendingDatasource;
use Kinintel\Objects\Datasource\TestDatasource;
use Kinintel\Objects\Datasource\WebService\WebServiceDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\ExtendingDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class ExtendingDatasourceTest extends \PHPUnit\Framework\TestCase {

    private DatasourceService|MockObject $datasourceService;
    private DatasourceService|MockObject $originalDatasourceService;
    private WebServiceDatasource $wsDatasource;

    public function setUp(): void {
        $this->originalDatasourceService = Container::instance()->get(DatasourceService::class);
        $this->datasourceService = MockObjectProvider::mock(DatasourceService::class);
        $this->wsDatasource = new WebServiceDatasource(
            new WebserviceDataSourceConfig("https://rdap.verisign.com/com/v1/domain/netistrar.com",
                resultFormatConfig: ["singleResult" => true],
                columns: [
                    new Field("objectClassName", "ObjectName", "[[objectClassName]]"),
                    new Field("ldhName", "LDH", "[[ldhName]]"),
                ],
            ),
            null,
            null,
            "test",
            "Domain Thing"
        );
    }
    public function tearDown(): void {
        Container::instance()->set(DatasourceService::class, $this->originalDatasourceService);
        parent::tearDown();
    }

    public function testExtendingSourceWithConfiguredBaseDatasourceIsMaterialisedCorrectly() {

        $baseInstance = MockObjectProvider::mock(DatasourceInstance::class);
        $baseDatasource = MockObjectProvider::mock(Datasource::class);
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

        $baseInstance = MockObjectProvider::mock(DatasourceInstance::class);
        $baseDatasource = MockObjectProvider::mock(Datasource::class);
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

        $baseInstance = MockObjectProvider::mock(DatasourceInstance::class);
        $baseDatasource = MockObjectProvider::mock(Datasource::class);
        $baseInstance->returnValue("returnDataSource", $baseDatasource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            $baseInstance, ["base"]);

        // Make a filter transformation (test == test)
        $firstTransformationInstance = MockObjectProvider::mock(TransformationInstance::class);
        $firstTransformation = new FilterTransformation([new Filter("test", "test")]);
        $firstTransformationInstance->returnValue("returnTransformation", $firstTransformation);

        // Parameters are passed through all transformations
        $transformedDatasource1 = MockObjectProvider::mock(Datasource::class);
        $baseDatasource->returnValue("applyTransformation", $transformedDatasource1, [$firstTransformation, ["A" => "Yes", "B" => "No"]]);

        $secondTransformationInstance = MockObjectProvider::mock(TransformationInstance::class);
        $secondTransformation = new FilterTransformation([new Filter("other", "other")]);
        $secondTransformationInstance->returnValue("returnTransformation", $secondTransformation);
        $transformedDatasource2 = MockObjectProvider::mock(Datasource::class);
        $transformedDatasource1->returnValue("applyTransformation", $transformedDatasource2, [$secondTransformation, ["A" => "Yes", "B" => "No"]]);

        $transformedDatasource2->returnValue("materialise", "BINGO BANGO", [["A" => "Yes", "B" => "No"]]);

        $config = new ExtendingDatasourceConfig("base", [
            $firstTransformationInstance, $secondTransformationInstance
        ]);
        $extendingDatasource = new ExtendingDatasource($config, $this->datasourceService);

        $this->assertEquals("BINGO BANGO", $extendingDatasource->materialise(["A" => "Yes", "B" => "No"]));

    }


    public function testCanApplyTransformationsToAnExtendingDataSourceAndTheseAreAppliedAfterwards() {

        // Make a base datasource
        $baseInstance = MockObjectProvider::mock(DatasourceInstance::class);
        $baseDatasource = MockObjectProvider::mock(Datasource::class);
        $baseInstance->returnValue("returnDataSource", $baseDatasource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            $baseInstance, ["base"]);

        // Make a filter (test == test)
        $firstTransformationInstance = MockObjectProvider::mock(TransformationInstance::class);
        $firstTransformation = new FilterTransformation([new Filter("test", "test")]);
        $firstTransformationInstance->returnValue("returnTransformation", $firstTransformation);

        // Make a filter (other == other)
        $secondTransformationInstance = MockObjectProvider::mock(TransformationInstance::class);
        $secondTransformation = new FilterTransformation([new Filter("other", "other")]);
        $secondTransformationInstance->returnValue("returnTransformation", $secondTransformation);

        // Make a filter (final == final)
        $thirdTransformationInstance = MockObjectProvider::mock(TransformationInstance::class);
        $thirdTransformation = new FilterTransformation([new Filter("final", "final")]);
        $thirdTransformationInstance->returnValue("returnTransformation", $thirdTransformation);

        // When we transform $baseDatasource with the first transformation, we get $transformedDatasource1
        $transformedDatasource1 = MockObjectProvider::mock(Datasource::class);
        $baseDatasource->returnValue("applyTransformation", $transformedDatasource1, [$firstTransformation, ["A" => "Yes", "B" => "No"]]);

        // When we transform $transformedDatasource1 with the second transformation, we get $transformedDatasource2
        $transformedDatasource2 = MockObjectProvider::mock(Datasource::class);
        $transformedDatasource1->returnValue("applyTransformation", $transformedDatasource2, [$secondTransformation, ["A" => "Yes", "B" => "No"]]);

        // When we transform $transformedDatasource2 with the second transformation, we get $transformedDatasource3
        $transformedDatasource3 = MockObjectProvider::mock(Datasource::class);
        $transformedDatasource2->returnValue("applyTransformation", $transformedDatasource3, [$thirdTransformation, ["A" => "Yes", "B" => "No"]]);

        $transformedDatasource3->returnValue("materialise", "BINGO BANGO", [["A" => "Yes", "B" => "No"]]);

        $config = new ExtendingDatasourceConfig("base", [
            $firstTransformationInstance, $secondTransformationInstance
        ]);
        $extendingDatasource = new ExtendingDatasource($config, $this->datasourceService);
        $finalExtendingDatasource = $extendingDatasource->applyTransformation($thirdTransformation, ["A" => "Yes", "B" => "No"]);

        $this->assertEquals("BINGO BANGO", $finalExtendingDatasource->materialise(["A" => "Yes", "B" => "No"]));


    }

    public function testCanExtendARealDatasource() {


        $mockDatasourceService = MockObjectProvider::mock(DatasourceService::class);
        $mockDsi = MockObjectProvider::mock(DatasourceInstance::class);
        $mockDsi->returnValue("returnDataSource", $this->wsDatasource);
        $mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockDsi, ["test"]);

        $extendingDatasource = new ExtendingDatasource(new ExtendingDatasourceConfig("test", []), $mockDatasourceService);

        $data = $extendingDatasource->materialiseDataset()->getAllData();

        $this->assertSame(
            [
                "objectClassName" => "domain",
                "ldhName" => "NETISTRAR.COM"
            ],
            $data[0]
        );


        $extendingDatasource = new ExtendingDatasource(new ExtendingDatasourceConfig("test", [
            new TransformationInstance(ColumnsTransformation::class,
                new ColumnsTransformation([new Field("objectClassName")])
            )
        ]), $mockDatasourceService);

        $data = $extendingDatasource->materialiseDataset()->getAllData();

        $this->assertSame(
            [
                "objectClassName" => "domain",
            ],
            $data[0]
        );
    }

    public function testCanExtendANestedDatasource() {
        $mockDatasourceService = MockObjectProvider::mock(DatasourceService::class);
        $mockDsi1 = MockObjectProvider::mock(DatasourceInstance::class);
        $mockDsi1->returnValue("returnDataSource", $this->wsDatasource);
        $mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockDsi1, ["test1"]);

        $innerExtendingDatasource = new ExtendingDatasource(new ExtendingDatasourceConfig("test1", [
            new TransformationInstance(ColumnsTransformation::class,
                new ColumnsTransformation([new Field("objectClassName")])
            )
        ]), $mockDatasourceService);

        $mockDsi2 = MockObjectProvider::mock(DatasourceInstance::class);
        $mockDsi2->returnValue("returnDataSource", $innerExtendingDatasource);
        $mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockDsi2, ["test2"]);
        $outerExtendingDatasource = new ExtendingDatasource(new ExtendingDatasourceConfig("test2", [
            new TransformationInstance(ColumnsTransformation::class,
                new ColumnsTransformation([new Field("objectClassName")])
            )
        ]), $mockDatasourceService);

        $data = $outerExtendingDatasource->materialiseDataset()->getAllData();
        $this->assertSame(
            [
                "objectClassName" => "domain",
            ],
            $data[0]
        );
    }

}