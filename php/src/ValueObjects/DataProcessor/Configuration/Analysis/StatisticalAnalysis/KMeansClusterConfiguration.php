<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis;

class KMeansClusterConfiguration
{

    /**
     * The number of centroids to place
     * @var int
     * @required
     */
    private $numberOfClusters;

    /**
     * @var int
     */
    private $timestepLimit;

    /**
     * Constructor
     *
     * @param int $numberOfClusters
     * @param int $timestepLimit
     */
    public function __construct($numberOfClusters, $timestepLimit = 1000)
    {
        $this->numberOfClusters = $numberOfClusters;
        $this->timestepLimit = $timestepLimit;
    }

    /**
     * @return int
     */
    public function getTimestepLimit()
    {
        return $this->timestepLimit;
    }

    /**
     * @param int $timestepLimit
     */
    public function setTimestepLimit($timestepLimit)
    {
        $this->timestepLimit = $timestepLimit;
    }

    /**
     * @return int
     */
    public function getNumberOfClusters()
    {
        return $this->numberOfClusters;
    }

    /**
     * @param int $numberOfClusters
     */
    public function setNumberOfClusters($numberOfClusters)
    {
        $this->numberOfClusters = $numberOfClusters;
    }


}