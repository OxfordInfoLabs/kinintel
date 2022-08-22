<?php

namespace Kinintel\Test\Services\Util\Analysis\StatisticalAnalysis\Cluster;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\HierarchicalCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EuclideanDistanceCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\PearsonCorrelationDistanceCalculator;
use Kinintel\ValueObjects\Dataset\Field;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class HierarchicalClusterTest extends TestCase
{
    /**
     * @var HierarchicalCluster
     */
    private $hclust;

    public function setUp(): void
    {
        $this->hclust = new HierarchicalCluster();
    }

    public function testProcessesDatasetToMakeHierarchicalCluster(){
        $calculator = Container::instance()->get(EuclideanDistanceCalculator::class);

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
            ["id"=>0,"direct_children"=>[],"elements"=>["A"],"size"=>1],
            ["id"=>1,"direct_children"=>[],"elements"=>["B"],"size"=>1],
            ["id"=>2,"direct_children"=>[],"elements"=>["C"],"size"=>1],
            ["id"=>3,"direct_children"=>[],"elements"=>["D"],"size"=>1],
            ["id"=>4,"direct_children"=>[0,1],"elements"=>["A","B"],"size"=>2],
            ["id"=>5,"direct_children"=>[2,3],"elements"=>["C","D"],"size"=>2],
            ["id"=>6,"direct_children"=>[4,5],"elements"=>["A","B","C","D"],"size"=>4]
        ];
        $this->assertTrue($resultsDataset instanceof ArrayTabularDataset);
        $this->assertEquals($expectedColumns, $resultsDataset->getColumns());
        $this->assertEquals($expectedResults, $resultsDataset->getAllData());
    }
}