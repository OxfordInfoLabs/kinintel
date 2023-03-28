<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance;

class EuclideanMetricCalculator implements MetricCalculator {

    public function getCustomExpression($config) {
        return "SQRT(SUM(([[" . $config->getValueFieldName() . "]]-[[" . $config->getValueFieldName() . "_2]])*([[" . $config->getValueFieldName() . "]]-[[" . $config->getValueFieldName() . "_2]])))";
    }

    public function getTitle() {
        return "Euclidean";
    }

    public function calculateDistance($vector1, $vector2) {

        if (sizeof($vector1) < sizeof($vector2)) {
            $temp = $vector1;
            $vector1 = $vector2;
            $vector2 = $temp;
        }

        $distSq = 0;

        for ($i = 0; $i < count($vector1); $i++) {
            $distSq += ($vector1[$i] - ($vector2[$i] ?? 0)) * ($vector1[$i] - ($vector2[$i] ?? 0));
        }

        return sqrt($distSq);
    }
}