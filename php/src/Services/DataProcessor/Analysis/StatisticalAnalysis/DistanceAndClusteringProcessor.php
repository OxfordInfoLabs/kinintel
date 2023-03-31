<?php


namespace Kinintel\Services\DataProcessor\Analysis\StatisticalAnalysis;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\HierarchicalCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster\KMeansCluster;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\MetricCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\MetricProcessor;
use Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis\DistanceAndClusteringProcessorConfiguration;

class DistanceAndClusteringProcessor implements DataProcessor {

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
    public function __construct($hierarchicalCluster, $kmeansCluster) {
        $this->hierarchicalCluster = $hierarchicalCluster;
        $this->kmeansCluster = $kmeansCluster;
    }


    public function getConfigClass() {
        return DistanceAndClusteringProcessorConfiguration::class;
    }

    /**
     * @param DataProcessorInstance $instance
     * @return mixed[]
     */
    public function process($instance) {
        /**
         * @var DistanceAndClusteringProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        $distanceProcessor = Container::instance()->getInterfaceImplementation(MetricProcessor::class, $config->getDistanceMetric());
        $distanceCalculator = Container::instance()->getInterfaceImplementation(MetricCalculator::class, $config->getDistanceMetric());

        $distanceDatasourceInstance = $distanceProcessor->process($instance, $distanceCalculator);

        if ($config->isHierarchicalCluster() || $config->isKmeansCluster()) {
            $distanceDataset = $distanceDatasourceInstance->returnDataSource()->materialise();
        }

        $resultsHierarchical = $config->isHierarchicalCluster() ? $this->hierarchicalCluster->process($distanceDataset, $distanceCalculator) : null;
        $resultsKMeans = $config->isKmeansCluster() ? $this->kmeansCluster->process($config->getKmeansClusterConfiguration(), $distanceDataset, $distanceCalculator) : null;

        return [$resultsHierarchical, $resultsKMeans];
    }
}