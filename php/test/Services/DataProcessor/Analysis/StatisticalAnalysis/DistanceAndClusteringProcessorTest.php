<?php

namespace Kinintel\Test\Services\DataProcessor\Analysis\StatisticalAnalysis;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Controllers\API\Datasource;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\DataProcessor\Analysis\StatisticalAnalysis\DistanceAndClusteringProcessor;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\HierarchicalCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\KMeansCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\DistanceCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EquationDistanceProcessor;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EuclideanDistanceCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\PearsonCorrelationDistanceCalculator;
use Kinintel\TestBase;
use Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis\DistanceAndClusteringProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis\KMeansClusterConfiguration;

include_once "autoloader.php";

class DistanceAndClusteringProcessorTest extends TestBase
{
    /**
     * @var DistanceAndClusteringProcessor
     */
    private $distanceAndClusteringProcessor;

    /**
     * @var MockObject
     */
    private $hierarchicalCluster;

    /**
     * @var MockObject
     */
    private $kMeansCluster;

    /**
     * @var MockObject
     */
    private $equationProcessor;

    /**
     * @var MockObject
     */
    private $pearsonCalculator;

    /**
     * @var MockObject
     */
    private $euclideanCalculator;

    public function setUp(): void
    {
        $this->hierarchicalCluster = MockObjectProvider::instance()->getMockInstance(HierarchicalCluster::class);
        $this->kMeansCluster = MockObjectProvider::instance()->getMockInstance(KMeansCluster::class);

        $this->equationProcessor = MockObjectProvider::instance()->getMockInstance(EquationDistanceProcessor::class);
        Container::instance()->set(EquationDistanceProcessor::class, $this->equationProcessor);
        $this->distanceAndClusteringProcessor = new DistanceAndClusteringProcessor($this->hierarchicalCluster, $this->kMeansCluster);
    }

    public function testCanMakeADistanceDatasourceForAConfigWithPearson()
    {
        $pearsonCalculator = MockObjectProvider::instance()->getMockInstance(PearsonCorrelationDistanceCalculator::class);
        Container::instance()->set(PearsonCorrelationDistanceCalculator::class, $pearsonCalculator);

        $config = new DistanceAndClusteringProcessorConfiguration("sourceKey", "setId", "Company", "Department", "BurnRate", DistanceAndClusteringProcessorConfiguration::DISTANCE_PEARSON, false, false);
        $instance = new DataProcessorInstance("test", "Test Instance", "distanceandclustering", $config);

        $this->distanceAndClusteringProcessor->process($instance);

        $this->assertTrue($this->equationProcessor->methodWasCalled("process", [$instance, $pearsonCalculator]));
    }

    public function testCanMakeADistanceDatasourceForAConfigWithEuclidean()
    {
        $euclideanCalculator = MockObjectProvider::instance()->getMockInstance(EuclideanDistanceCalculator::class);
        Container::instance()->set(EuclideanDistanceCalculator::class, $euclideanCalculator);

        $config = new DistanceAndClusteringProcessorConfiguration("sourceKey", "setId", "Company", "Department", "BurnRate", DistanceAndClusteringProcessorConfiguration::DISTANCE_EUCLIDEAN, false, false);
        $instance = new DataProcessorInstance("test", "Test Instance", "distanceandclustering", $config);

        $this->distanceAndClusteringProcessor->process($instance);

        $this->assertTrue($this->equationProcessor->methodWasCalled("process", [$instance, $euclideanCalculator]));
    }

    public function testCanMakeAHierarchicalClusterDatasource()
    {
        $pearsonCalculator = MockObjectProvider::instance()->getMockInstance(PearsonCorrelationDistanceCalculator::class);
        Container::instance()->set(PearsonCorrelationDistanceCalculator::class, $pearsonCalculator);

        $config = new DistanceAndClusteringProcessorConfiguration("sourceKey", "setId", "Company", "Department", "BurnRate", DistanceAndClusteringProcessorConfiguration::DISTANCE_PEARSON, true, false);
        $instance = new DataProcessorInstance("test", "Test Instance", "distanceandclustering", $config);

        $returnDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $returnDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $returnDataset = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);
        $returnDatasource->returnValue("materialise", $returnDataset, []);
        $returnDatasourceInstance->returnValue("returnDataSource", $returnDatasource, []);
        $this->equationProcessor->returnValue("process", $returnDatasourceInstance, [$instance, $pearsonCalculator]);

        $this->distanceAndClusteringProcessor->process($instance);

        $this->assertTrue($this->equationProcessor->methodWasCalled("process", [$instance, $pearsonCalculator]));

        $this->assertTrue($this->hierarchicalCluster->methodWasCalled("process", [$returnDataset, $pearsonCalculator]));
    }

    public function testCanMakeAKMeansClusterDatasource()
    {
        $euclideanCalculator = MockObjectProvider::instance()->getMockInstance(EuclideanDistanceCalculator::class);
        Container::instance()->set(EuclideanDistanceCalculator::class, $euclideanCalculator);

        $config = new DistanceAndClusteringProcessorConfiguration("sourceKey", "setId", "Company", "Department", "BurnRate", DistanceAndClusteringProcessorConfiguration::DISTANCE_EUCLIDEAN, false, true);
        $instance = new DataProcessorInstance("test", "Test Instance", "distanceandclustering", $config);

        $returnDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $returnDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $returnDataset = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);
        $returnDatasource->returnValue("materialise", $returnDataset, []);
        $returnDatasourceInstance->returnValue("returnDataSource", $returnDatasource, []);
        $this->equationProcessor->returnValue("process", $returnDatasourceInstance, [$instance, $euclideanCalculator]);

        $this->distanceAndClusteringProcessor->process($instance);

        $this->assertTrue($this->equationProcessor->methodWasCalled("process", [$instance, $euclideanCalculator]));
        $this->assertTrue($this->kMeansCluster->methodWasCalled("process", [$config->getKmeansClusterConfiguration(), $returnDataset]));
    }

    public function testCanMakeHierarchicalAndKMeansClusterDatasource()
    {
        $euclideanCalculator = MockObjectProvider::instance()->getMockInstance(EuclideanDistanceCalculator::class);
        Container::instance()->set(EuclideanDistanceCalculator::class, $euclideanCalculator);

        $config = new DistanceAndClusteringProcessorConfiguration("sourceKey", "setId", "Company", "Department", "BurnRate", DistanceAndClusteringProcessorConfiguration::DISTANCE_EUCLIDEAN, true, true);
        $instance = new DataProcessorInstance("test", "Test Instance", "distanceandclustering", $config);

        $returnDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $returnDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $returnDataset = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);
        $returnDatasource->returnValue("materialise", $returnDataset, []);
        $returnDatasourceInstance->returnValue("returnDataSource", $returnDatasource, []);
        $this->equationProcessor->returnValue("process", $returnDatasourceInstance, [$instance, $euclideanCalculator]);

        $this->distanceAndClusteringProcessor->process($instance);

        $this->assertTrue($this->equationProcessor->methodWasCalled("process", [$instance, $euclideanCalculator]));
        $this->assertTrue($this->hierarchicalCluster->methodWasCalled("process", [$returnDataset, $euclideanCalculator]));
        $this->assertTrue($this->kMeansCluster->methodWasCalled("process", [$config->getKmeansClusterConfiguration(), $returnDataset]));
    }

    public function testValidationRulesCorrectlyDefinedForConfig()
    {
        $config = new DistanceAndClusteringProcessorConfiguration(null, null, null, null, null, "BADDISTANCE", true, true, new KMeansClusterConfiguration(null));
        $instance = new DataProcessorInstance("test", "Test", "distanceandclustering", $config);
        $validationErrors = $instance->validate();

        $this->assertEquals(6, sizeof($validationErrors["config"]));
    }
}