<?php

namespace Kinintel\Test\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\MetricCalculator;

class TestMetricCalculator implements MetricCalculator {
    
    /**
     * @param $config
     * @return string
     */
    public function getCustomExpression($config) {
        return "kfn: " . $config->getKeyFieldName() . ", cfn: " . $config->getComponentFieldName() . ", vfn: " . $config->getValueFieldName();
    }

    public function getTitle() {
        return "Test";
    }

    public function calculateDistance($vector1, $vector2) {
        //TODO: Implement calculateDistance() method.
    }
}