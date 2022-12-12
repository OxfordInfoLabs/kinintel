<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Kinintel\ValueObjects\Util\Analysis\StatisticalAnalysis\Distance\DistanceConfig;

/**
 * @implementation euclidean \Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EquationMetricProcessor
 * @implementation pearson \Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EquationMetricProcessor
 */
interface MetricProcessor {

    /**
     * Only required method to process based on a distance config
     * Takes in a distance config and writes a hierarchical cluster to a dataset
     *
     * @param DistanceConfig $config
     * @param MetricCalculator $calculator
     * @return mixed
     */
    public function process($config, $calculator);

}