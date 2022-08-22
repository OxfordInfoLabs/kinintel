<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Exception;
use Kinikit\Core\Validation\ValidationException;

class PearsonCorrelationDistanceCalculator implements DistanceCalculator
{
    public function getCustomExpression($config){
        $cfn = $config->getComponentFieldName();
        $vfn = $config->getValueFieldName();

        $pCCCorrelationSum = "SUM([[".$vfn."]]*[[".$vfn."_2]])";
        $pCCPhraseFactor = "1/COUNT([[$cfn]])"; // 1/n
        $pCCFirstFrequencySum = "SUM([[".$vfn."]])";
        $pCCSecondFrequencySum = "SUM([[".$vfn."_2]])";
        $pCCFirstFrequencySq = "SUM(POW([[".$vfn."]], 2))";
        $pCCSecondFrequencySq = "SUM(POW([[".$vfn."_2]], 2))";


        return "1-([[$pCCCorrelationSum]] - [[$pCCPhraseFactor]]*[[$pCCFirstFrequencySum]]*[[$pCCSecondFrequencySum]])/SQRT(
            ([[$pCCFirstFrequencySq]] - [[$pCCPhraseFactor]]*[[$pCCFirstFrequencySum]]*[[$pCCFirstFrequencySum]])
            *([[$pCCSecondFrequencySq]] - [[$pCCPhraseFactor]]*[[$pCCSecondFrequencySum]]*[[$pCCSecondFrequencySum]])
        )";
    }

    public function getTitle()
    {
        return "Pearson Correlation";
    }

    /**
     * @param $vector1
     * @param $vector2
     * @return float
     */
    public function calculateDistance($vector1, $vector2) //Gets ONE MINUS Pearson Correlation
    {
        $length= count($vector1);
        if (count($vector2) != $length){
            throw new Exception("The two datasets aren't the same length.");
        }
        $mean1=array_sum($vector1) / $length;
        $mean2=array_sum($vector2) / $length;

        $axb=0;
        $a2=0;
        $b2=0;

        for($i=0;$i<$length;$i++)
        {
            $a=$vector1[$i]-$mean1;
            $b=$vector2[$i]-$mean2;
            $axb=$axb+($a*$b);
            $a2=$a2+ pow($a,2);
            $b2=$b2+ pow($b,2);
        }

        $corr = $axb == 0 ? 0 : $axb / sqrt($a2*$b2);

        return 1-$corr;
    }
}