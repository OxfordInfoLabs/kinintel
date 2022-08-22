<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Kinintel\ValueObjects\Util\Analysis\StatisticalAnalysis\Distance\DistanceConfig;

/**
 * @implementation euclidean \Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EquationDistanceProcessor
 * @implementation pearson \Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EquationDistanceProcessor
 */
interface DistanceProcessor
{
    /**
     * Only required method to process based on a distance config
     * Takes in a distance config and writes a hierarchical cluster to a dataset
     *
     * @param DistanceConfig $config
     * @param DistanceCalculator $calculator
     * @return mixed
     */
    public function process($config, $calculator);
}