<?php

namespace Kinintel\Test\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Controllers\API\Datasource;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\CustomDatasourceService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EquationMetricProcessor;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseExpression;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;
use Kinintel\ValueObjects\Util\Analysis\StatisticalAnalysis\Distance\DistanceConfig;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class EquationDistanceProcessorTest extends TestCase {

    /**
     * @var TestMetricCalculator
     */
    private $testProcessor;

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * @var CustomDatasourceService
     */
    private $customDatasourceService;

    public function setUp(): void {

        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->customDatasourceService = MockObjectProvider::instance()->getMockInstance(CustomDatasourceService::class);

        $this->testProcessor = new EquationMetricProcessor($this->datasourceService, $this->datasetService, $this->customDatasourceService);
    }

    public function testAsksForDistanceCalculationQueryAndWritesToSnapshot() {
        $mockSnapshotInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockSnapshotSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockSnapshotInstance->returnValue("returnDataSource", $mockSnapshotSource);

        $calculator = Container::instance()->get(TestMetricCalculator::class);

        $minimalData = [
            [
                "document" => "A",
                "document_2" => "B",
                "distance" => 25],
            [
                "document" => "B",
                "document_2" => "C",
                "distance" => 4],
            [
                "document" => "A",
                "document_2" => "C",
                "distance" => 42]
        ];

        $minimalDataset = new ArrayTabularDataset([new Field("document"), new Field("document_2"), new Field("distance")], $minimalData);

        $expectedJoinTransformation = new JoinTransformation("sourceKey", null, [],
            new FilterJunction([
                new Filter("[[phrase]]", "[[phrase]]", Filter::FILTER_TYPE_EQUALS),
                new Filter("[[document]]", "[[document]]", Filter::FILTER_TYPE_GREATER_THAN)
            ]), [new Field("document"), new Field("frequency")], true);

        $expectedSummariseTransformation = new SummariseTransformation(["document", "document_2"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, null,
                "kfn: document, cfn: phrase, vfn: frequency", "distance")
        ]);

        $this->datasourceService->returnValue("getEvaluatedDataSource", $minimalDataset, [
            "sourceKey", [], [
                new TransformationInstance("join", $expectedJoinTransformation),
                new TransformationInstance("summarise", $expectedSummariseTransformation)
            ]
        ]);

        Container::instance()->set(DatasourceService::class, $this->datasourceService);
        Container::instance()->set(CustomDatasourceService::class, $this->customDatasourceService);

        $config = new DistanceConfig("sourceKey", null, "document", "phrase", "frequency");
        $instance = new DataProcessorInstance("fresh", "Vectors", "distanceandclustering", $config, "thevectorproject", 20);

        $this->customDatasourceService->returnValue("createTabularSnapshotDatasourceInstance", $mockSnapshotInstance,
            [
                "Vectors: Test Distance", [
                new Field("document"),
                new Field("document_2"),
                new Field("distance")],
                "thevectorproject",
                20
            ]
        );

        $mockSnapshotSource->returnValue("materialise", new ArrayTabularDataset([new Field("document"), new Field("document_2"), new Field("distance")], $minimalData), []);

        $distanceDataset = $this->testProcessor->process($instance, $calculator);

        $this->assertTrue($this->customDatasourceService->methodWasCalled("createTabularSnapshotDatasourceInstance", [
            "Vectors: Test Distance", [
                new Field("document"),
                new Field("document_2"),
                new Field("distance")],
            "thevectorproject",
            20
        ]));

        $this->assertEquals(
            ["sourceKey", [], [
                new TransformationInstance("join", $expectedJoinTransformation),
                new TransformationInstance("summarise", $expectedSummariseTransformation)
            ]], $this->datasourceService->getMethodCallHistory("getEvaluatedDataSource")[0]);


        $secondWriteSet = new ArrayTabularDataset([new Field("document"), new Field("document_2"), new Field("distance")], [
            ["document" => "A",
                "document_2" => "A",
                "distance" => 0],
            ["document" => "B",
                "document_2" => "B",
                "distance" => 0],
            ["document" => "B",
                "document_2" => "A",
                "distance" => 25],
            ["document" => "C",
                "document_2" => "C",
                "distance" => 0],
            ["document" => "C",
                "document_2" => "B",
                "distance" => 4],
            ["document" => "C",
                "document_2" => "A",
                "distance" => 42]
        ]);

        //Check for the first update containing the minimal distance data
        $this->assertEquals([new ArrayTabularDataset([new Field("document"), new Field("document_2"), new Field("distance")], $minimalData), UpdatableDatasource::UPDATE_MODE_ADD],
            $mockSnapshotSource->getMethodCallHistory("update")[0]);
        //Check for the first update containing the derived distance data
        $this->assertEquals([$secondWriteSet, UpdatableDatasource::UPDATE_MODE_ADD],
            $mockSnapshotSource->getMethodCallHistory("update")[1]);

        $this->assertTrue($distanceDataset instanceof DatasourceInstance);
    }
}