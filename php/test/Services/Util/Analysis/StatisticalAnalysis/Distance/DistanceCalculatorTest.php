<?php

namespace Kinintel\Test\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EuclideanMetricCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\PearsonCorrelationMetricCalculator;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DistanceCalculatorTest extends TestCase {

    public function testEuclideanDistanceCalculation() {

        $calculator = new EuclideanMetricCalculator();

        $vector1 = [0, 0];
        $vector2 = [3, 4];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(5, $distance);

        $vector1 = [1, 2, 10, -4];
        $vector2 = [-1, 6, 10, 4.5];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(9.604686356149273, $distance);

        $vector1 = [1, 2, 3];
        $vector2 = [1, 2, 3];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(0, $distance);

        $vector1 = [0, 0];
        $vector2 = [5, 0, 12];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(13, $distance);

        $vector1 = [0, 0, 0];
        $vector2 = [4, 3];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(5, $distance);
    }

    public function testPearsonDistanceCalculation() {

        $calculator = new PearsonCorrelationMetricCalculator();

        $vector1 = [1, 2, 3];
        $distance = $calculator->calculateDistance($vector1, $vector1);
        $this->assertEquals(1, $distance);

        $vector1 = [0, 0, 0];
        $vector2 = [1, 1, 1];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(0, $distance);

        $vector1 = [1, 2, 3];
        $vector2 = [3, 2, 1];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(-1, $distance);

        $vector1 = [1, 2, 3];
        $vector2 = [1, 3, 2];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(0.5, $distance);


        try {
            $distance = $calculator->calculateDistance($vector1, [2, 1]);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals("The two datasets aren't the same length.", $e->getMessage());
        }

    }
}
