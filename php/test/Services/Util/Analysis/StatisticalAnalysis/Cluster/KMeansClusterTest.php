<?php

namespace Kinintel\Test\Services\Util\Analysis\StatisticalAnalysis\Cluster;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\HierarchicalCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\KMeansCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EuclideanDistanceCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\PearsonCorrelationDistanceCalculator;
use Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis\KMeansClusterConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class KMeansClusterTest extends TestCase
{
    /**
     * @var KMeansCluster
     */
    private $kclust;

    public function setUp(): void
    {
        $this->kclust = new KMeansCluster();
    }

    public function testProcessesDatasetToMakeKMeansCluster(){
        $calculator = Container::instance()->get(PearsonCorrelationDistanceCalculator::class);
        $config = new KMeansClusterConfiguration(4);

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
        $resultsDataset = $this->kclust->process($config, $testDataset,$calculator);

        $expectedColumns = [
            new Field("id"),
            new Field("elements"),
            new Field("size")
        ];

        $expectedResults = [
            ["id"=>0,"elements"=>["A"],"size"=>1],
            ["id"=>1,"elements"=>["B"],"size"=>1],
            ["id"=>2,"elements"=>["C"],"size"=>1],
            ["id"=>3,"elements"=>["D"],"size"=>1]

        ];

        print_r($resultsDataset->getAllData());

        $this->assertTrue($resultsDataset instanceof ArrayTabularDataset);
        $this->assertEquals($expectedColumns, $resultsDataset->getColumns());
        $this->assertEquals($expectedResults, $resultsDataset->getAllData());
    }
}
