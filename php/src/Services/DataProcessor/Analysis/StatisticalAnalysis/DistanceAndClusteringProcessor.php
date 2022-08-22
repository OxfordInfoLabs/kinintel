<?php


namespace Kinintel\Services\DataProcessor\Analysis\StatisticalAnalysis;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\HierarchicalCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\KMeansCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\DistanceCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\DistanceProcessor;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EquationDistanceProcessor;
use Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis\DistanceAndClusteringProcessorConfiguration;

class DistanceAndClusteringProcessor implements DataProcessor
{

    /**
     * @var HierarchicalCluster
     */
    private $hierarchicalCluster;

    /**
     * @var KMeansCluster
     */
    private $kmeansCluster;

    /**
     * @param HierarchicalCluster $hierarchicalCluster
     * @param KMeansCluster $kmeansCluster
     */
    public function __construct($hierarchicalCluster, $kmeansCluster)
    {
        $this->hierarchicalCluster = $hierarchicalCluster;
        $this->kmeansCluster = $kmeansCluster;
    }


    public function getConfigClass()
    {
        return DistanceAndClusteringProcessorConfiguration::class;
    }

    /**
     * @param DataProcessorInstance $instance
     * @return void
     */
    public function process($instance)
    {
        $config = $instance->returnConfig();
        $distanceProcessor = Container::instance()->getInterfaceImplementation(DistanceProcessor::class, $config->getDistanceMetric());
        $distanceCalculator = Container::instance()->getInterfaceImplementation(DistanceCalculator::class, $config->getDistanceMetric());

        $distanceDatasourceInstance = $distanceProcessor->process($instance, $distanceCalculator);

        if($config->isHierarchicalCluster() || $config->isKmeansCluster()){
            $distanceDataset = $distanceDatasourceInstance->returnDataSource()->materialise();
            $config->isHierarchicalCluster() ? $this->hierarchicalCluster->process($distanceDataset, $distanceCalculator) : false;
            $config->isKmeansCluster() ? $this->kmeansCluster->process($config->getKmeansClusterConfiguration(), $distanceDataset) : false;
        }
    }
}