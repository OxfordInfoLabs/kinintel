<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Kinintel\ValueObjects\Util\Analysis\StatisticalAnalysis\Distance\DistanceConfig;

class EuclideanDistanceCalculator implements DistanceCalculator
{
    public function getCustomExpression($config){
        return "SQRT(SUM(([[" . $config->getValueFieldName() . "]]-[[". $config->getValueFieldName() . "_2]])*([[". $config->getValueFieldName() . "]]-[[". $config->getValueFieldName() . "_2]])))";
    }

    public function getTitle()
    {
        return "Euclidean";
    }

    public function calculateDistance($vector1, $vector2)
    {
        $distSq = 0;
        for ($i = 0; $i<count($vector1); $i++){
            $distSq += ($vector1[$i] - $vector2[$i])*($vector1[$i] - $vector2[$i]);
        }
        return sqrt($distSq);
    }
}