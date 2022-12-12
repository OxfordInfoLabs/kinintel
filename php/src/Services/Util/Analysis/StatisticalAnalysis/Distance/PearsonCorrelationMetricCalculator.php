<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance;

class PearsonCorrelationMetricCalculator implements MetricCalculator {

    public function getCustomExpression($config) {
        $cfn = $config->getComponentFieldName();
        $vfn = $config->getValueFieldName();

        $pCCCorrelationSum = "SUM([[" . $vfn . "]]*[[" . $vfn . "_2]])";
        $pCCPhraseFactor = "1/COUNT([[$cfn]])"; // 1/n
        $pCCFirstFrequencySum = "SUM([[" . $vfn . "]])";
        $pCCSecondFrequencySum = "SUM([[" . $vfn . "_2]])";
        $pCCFirstFrequencySq = "SUM(POW([[" . $vfn . "]], 2))";
        $pCCSecondFrequencySq = "SUM(POW([[" . $vfn . "_2]], 2))";


        return "1-([[$pCCCorrelationSum]] - [[$pCCPhraseFactor]]*[[$pCCFirstFrequencySum]]*[[$pCCSecondFrequencySum]])/SQRT(
            ([[$pCCFirstFrequencySq]] - [[$pCCPhraseFactor]]*[[$pCCFirstFrequencySum]]*[[$pCCFirstFrequencySum]])
            *([[$pCCSecondFrequencySq]] - [[$pCCPhraseFactor]]*[[$pCCSecondFrequencySum]]*[[$pCCSecondFrequencySum]])
        )";
    }

    public function getTitle() {
        return "Pearson Correlation";
    }

    /**
     * @param $vector1
     * @param $vector2
     * @return float
     */
    public function calculateDistance($vector1, $vector2) {

        if ($vector1 == $vector2) {
            return 1;
        }

        $length = count($vector1);
        if (count($vector2) != $length) {
            throw new \Exception("The two datasets aren't the same length.");
        }

        $mean1 = array_sum($vector1) / $length;
        $mean2 = array_sum($vector2) / $length;

        $ab = 0;
        $a2 = 0;
        $b2 = 0;

        for ($i = 0; $i < $length; $i++) {
            $a = $vector1[$i] - $mean1;
            $b = $vector2[$i] - $mean2;
            $ab += ($a * $b);
            $a2 += pow($a, 2);
            $b2 += pow($b, 2);
        }

        $corr = $ab == 0 ? 0 : $ab / sqrt($a2 * $b2);

        return $corr;
    }
}