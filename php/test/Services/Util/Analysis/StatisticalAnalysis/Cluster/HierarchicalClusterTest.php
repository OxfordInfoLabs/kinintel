<?php

namespace Kinintel\Test\Services\Util\Analysis\StatisticalAnalysis\Cluster;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\HierarchicalCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EuclideanMetricCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\PearsonCorrelationMetricCalculator;
use Kinintel\ValueObjects\Dataset\Field;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class HierarchicalClusterTest extends TestCase {
    /**
     * @var HierarchicalCluster
     */
    private $hclust;

    public function setUp(): void {
        $this->hclust = new HierarchicalCluster();
    }

    public function testCanProcessSimpleDataset() {

        $calculator = Container::instance()->get(EuclideanMetricCalculator::class);

        $testDataset = new ArrayTabularDataset([
            new Field("document"),
            new Field("document_2"),
            new Field("distance")
        ], [
            ["document" => "A", "document_2" => "B", "distance" => 1],
            ["document" => "B", "document_2" => "A", "distance" => 1],

            ["document" => "C", "document_2" => "B", "distance" => 4],
            ["document" => "B", "document_2" => "C", "distance" => 4],

            ["document" => "C", "document_2" => "A", "distance" => 3],
            ["document" => "A", "document_2" => "C", "distance" => 3],

            ["document" => "D", "document_2" => "A", "distance" => 10],
            ["document" => "A", "document_2" => "D", "distance" => 10],

            ["document" => "D", "document_2" => "B", "distance" => 4],
            ["document" => "B", "document_2" => "D", "distance" => 4],

            ["document" => "D", "document_2" => "C", "distance" => 2],
            ["document" => "C", "document_2" => "D", "distance" => 2],

            ["document" => "A", "document_2" => "A", "distance" => 0],
            ["document" => "B", "document_2" => "B", "distance" => 0],
            ["document" => "C", "document_2" => "C", "distance" => 0],
            ["document" => "D", "document_2" => "D", "distance" => 0],
        ]);
        $resultsDataset = $this->hclust->process($testDataset, $calculator);

        $expectedColumns = [
            new Field("id"),
            new Field("direct_children"),
            new Field("elements"),
            new Field("size")
        ];

        $expectedResults = [
            ["id" => 0, "direct_children" => [], "elements" => ["A"], "size" => 1],
            ["id" => 1, "direct_children" => [], "elements" => ["B"], "size" => 1],
            ["id" => 2, "direct_children" => [], "elements" => ["C"], "size" => 1],
            ["id" => 3, "direct_children" => [], "elements" => ["D"], "size" => 1],
            ["id" => 4, "direct_children" => [0, 1], "elements" => ["A", "B"], "size" => 2],
            ["id" => 5, "direct_children" => [2, 3], "elements" => ["C", "D"], "size" => 2],
            ["id" => 6, "direct_children" => [4, 5], "elements" => ["A", "B", "C", "D"], "size" => 4]
        ];
        $this->assertTrue($resultsDataset instanceof ArrayTabularDataset);
        $this->assertEquals($expectedColumns, $resultsDataset->getColumns());
        $this->assertEquals($expectedResults, $resultsDataset->getAllData());
    }


    public function testCanProcessMoreComplexDataset() {

        $calculator = Container::instance()->get(EuclideanMetricCalculator::class);

        $testDataset = new ArrayTabularDataset([
            new Field("document"),
            new Field("document_2"),
            new Field("distance")
        ], [
            ["document" => "A", "document_2" => "A", "distance" => 0],
            ["document" => "A", "document_2" => "B", "distance" => 34],
            ["document" => "B", "document_2" => "A", "distance" => 34],
            ["document" => "A", "document_2" => "C", "distance" => 38],
            ["document" => "C", "document_2" => "A", "distance" => 38],
            ["document" => "A", "document_2" => "D", "distance" => 25],
            ["document" => "D", "document_2" => "A", "distance" => 25],
            ["document" => "A", "document_2" => "E", "distance" => 3],
            ["document" => "E", "document_2" => "A", "distance" => 3],
            ["document" => "A", "document_2" => "F", "distance" => 5],
            ["document" => "F", "document_2" => "A", "distance" => 5],
            ["document" => "A", "document_2" => "G", "distance" => 36],
            ["document" => "G", "document_2" => "A", "distance" => 36],


            ["document" => "B", "document_2" => "B", "distance" => 0],
            ["document" => "B", "document_2" => "C", "distance" => 8],
            ["document" => "C", "document_2" => "B", "distance" => 8],
            ["document" => "B", "document_2" => "D", "distance" => 31],
            ["document" => "D", "document_2" => "B", "distance" => 31],
            ["document" => "B", "document_2" => "E", "distance" => 29],
            ["document" => "E", "document_2" => "B", "distance" => 29],
            ["document" => "B", "document_2" => "F", "distance" => 27],
            ["document" => "F", "document_2" => "B", "distance" => 27],
            ["document" => "B", "document_2" => "G", "distance" => 20],
            ["document" => "G", "document_2" => "B", "distance" => 20],

            ["document" => "C", "document_2" => "C", "distance" => 0],
            ["document" => "C", "document_2" => "D", "distance" => 30],
            ["document" => "D", "document_2" => "C", "distance" => 30],
            ["document" => "C", "document_2" => "E", "distance" => 33],
            ["document" => "E", "document_2" => "C", "distance" => 33],
            ["document" => "C", "document_2" => "F", "distance" => 32],
            ["document" => "F", "document_2" => "C", "distance" => 32],
            ["document" => "C", "document_2" => "G", "distance" => 12],
            ["document" => "G", "document_2" => "C", "distance" => 12],

            ["document" => "D", "document_2" => "D", "distance" => 0],
            ["document" => "D", "document_2" => "E", "distance" => 25],
            ["document" => "E", "document_2" => "D", "distance" => 25],
            ["document" => "D", "document_2" => "F", "distance" => 18],
            ["document" => "F", "document_2" => "D", "distance" => 18],
            ["document" => "D", "document_2" => "G", "distance" => 24],
            ["document" => "G", "document_2" => "D", "distance" => 24],

            ["document" => "E", "document_2" => "E", "distance" => 0],
            ["document" => "E", "document_2" => "F", "distance" => 6],
            ["document" => "F", "document_2" => "E", "distance" => 6],
            ["document" => "E", "document_2" => "G", "distance" => 34],
            ["document" => "G", "document_2" => "E", "distance" => 34],

            ["document" => "F", "document_2" => "F", "distance" => 0],
            ["document" => "F", "document_2" => "G", "distance" => 22],
            ["document" => "G", "document_2" => "F", "distance" => 22],

            ["document" => "G", "document_2" => "G", "distance" => 0]
        ]);

        $resultsDataset = $this->hclust->process($testDataset, $calculator);

        $expectedColumns = [
            new Field("id"),
            new Field("direct_children"),
            new Field("elements"),
            new Field("size")
        ];

        $expectedResults = [
            ["id" => 0, "direct_children" => [], "elements" => ["A"], "size" => 1],
            ["id" => 1, "direct_children" => [], "elements" => ["B"], "size" => 1],
            ["id" => 2, "direct_children" => [], "elements" => ["C"], "size" => 1],
            ["id" => 3, "direct_children" => [], "elements" => ["D"], "size" => 1],
            ["id" => 4, "direct_children" => [], "elements" => ["E"], "size" => 1],
            ["id" => 5, "direct_children" => [], "elements" => ["F"], "size" => 1],
            ["id" => 6, "direct_children" => [], "elements" => ["G"], "size" => 1],
            ["id" => 7, "direct_children" => [0, 4], "elements" => ["A", "E"], "size" => 2],
            ["id" => 8, "direct_children" => [5, 7], "elements" => ["F", "A", "E"], "size" => 3],
            ["id" => 9, "direct_children" => [1, 2], "elements" => ["B", "C"], "size" => 2],
            ["id" => 10, "direct_children" => [8, 9], "elements" => ["F", "A", "E", "B", "C"], "size" => 5],
            ["id" => 11, "direct_children" => [6, 10], "elements" => ["G", "F", "A", "E", "B", "C"], "size" => 6],
            ["id" => 12, "direct_children" => [3, 11], "elements" => ["D", "G", "F", "A", "E", "B", "C"], "size" => 7]
        ];

        $this->assertTrue($resultsDataset instanceof ArrayTabularDataset);
        $this->assertEquals($expectedColumns, $resultsDataset->getColumns());
        $this->assertEquals($expectedResults, $resultsDataset->getAllData());

    }
}