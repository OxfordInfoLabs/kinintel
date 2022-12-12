<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Kinintel\ValueObjects\Util\Analysis\StatisticalAnalysis\Distance\DistanceConfig;

/**
 * @implementation euclidean \Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EuclideanMetricCalculator
 * @implementation pearson \Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\PearsonCorrelationMetricCalculator
 */
interface MetricCalculator {

    /**
     * Return the type of the processor as a string e.g. "Euclidean"
     * @return string
     */
    public function getTitle();

    /**
     * Calculate the distance based on two input arrays of values using this distance method.
     *
     * @param $vector1
     * @param $vector2
     * @return mixed
     */
    public function calculateDistance($vector1, $vector2);


    /**
     * @param DistanceConfig $config
     * @return string
     */
    public function getCustomExpression($config);

}