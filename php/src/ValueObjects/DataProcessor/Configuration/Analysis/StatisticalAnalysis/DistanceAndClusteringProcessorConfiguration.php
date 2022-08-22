<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis;

use Kinintel\ValueObjects\Util\Analysis\StatisticalAnalysis\Distance\DistanceConfig;

class DistanceAndClusteringProcessorConfiguration extends DistanceConfig
{

    /**
     * @var string
     * @required
     * @values euclidean,pearson
     */
    protected $distanceMetric;

    /**
     * @var bool
     */
    private $hierarchicalCluster;

    /**
     * @var bool
     */
    private $kmeansCluster;

    /**
     * @var KMeansClusterConfiguration
     */
    private $kmeansClusterConfiguration;

    const DISTANCE_EUCLIDEAN = "euclidean";
    const DISTANCE_PEARSON = "pearson";

    /**
     * Construct processor
     *
     * @param string $datasourceKey
     * @param string $datasetId
     * @param string $keyFieldName
     * @param string $componentFieldName
     * @param string $valueFieldName
     * @param string $distanceMetric
     * @param bool $hierarchicalCluster
     * @param bool $kmeansCluster
     * @param KMeansClusterConfiguration $kmeansClusterConfiguration
     */
    public function __construct($datasourceKey, $datasetId, $keyFieldName, $componentFieldName, $valueFieldName, $distanceMetric = self::DISTANCE_PEARSON, $hierarchicalCluster = false, $kmeansCluster = false,
                                $kmeansClusterConfiguration = null)
    {
        parent::__construct($datasourceKey, $datasetId, $keyFieldName, $componentFieldName, $valueFieldName);
        $this->hierarchicalCluster = $hierarchicalCluster;
        $this->kmeansCluster = $kmeansCluster;
        $this->kmeansClusterConfiguration = $kmeansClusterConfiguration;
        $this->distanceMetric = $distanceMetric;
    }

    /**
     * @return bool
     */
    public function isHierarchicalCluster()
    {
        return $this->hierarchicalCluster;
    }

    /**
     * @param bool $hierarchicalCluster
     */
    public function setHierarchicalCluster($hierarchicalCluster)
    {
        $this->hierarchicalCluster = $hierarchicalCluster;
    }

    /**
     * @return bool
     */
    public function isKmeansCluster()
    {
        return $this->kmeansCluster;
    }

    /**
     * @param bool $kmeansCluster
     */
    public function setKmeansCluster($kmeansCluster)
    {
        $this->kmeansCluster = $kmeansCluster;
    }

    /**
     * @return KMeansClusterConfiguration
     */
    public function getKmeansClusterConfiguration()
    {
        return $this->kmeansClusterConfiguration;
    }

    /**
     * @param KMeansClusterConfiguration $kmeansClusterConfiguration
     */
    public function setKmeansClusterConfiguration($kmeansClusterConfiguration)
    {
        $this->kmeansClusterConfiguration = $kmeansClusterConfiguration;
    }

    /**
     * @return string
     */
    public function getDistanceMetric()
    {
        return $this->distanceMetric;
    }

    /**
     * @param string $distanceMetric
     */
    public function setDistanceMetric($distanceMetric)
    {
        $this->distanceMetric = $distanceMetric;
    }


}