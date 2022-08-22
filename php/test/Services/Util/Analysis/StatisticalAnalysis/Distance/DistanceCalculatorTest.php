<?php

namespace Kinintel\Test\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Exception;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\ValidationException;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EuclideanDistanceCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\PearsonCorrelationDistanceCalculator;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DistanceCalculatorTest extends TestCase
{
    public function testEuclideanDistanceCalculation(){
        $calculator = Container::instance()->get(EuclideanDistanceCalculator::class);
        $vector1 = [1, 2, 10, -4];
        $vector2 = [-1, 6, 10, 4.5];
        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(9.60468635615, $distance);
    }

    public function testPearsonDistanceCalculation(){
        $calculator = Container::instance()->get(PearsonCorrelationDistanceCalculator::class);
        $vector1 = [1, 2, 10, -4];
        $vector2 = [-1, 6, 10, 4.5];
        $distance = $calculator->calculateDistance($vector1, $vector1);
        $this->assertEquals(1, $distance);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The two datasets aren't the same length.");
        $distance = $calculator->calculateDistance($vector1, [2, 1]);

        $distance = $calculator->calculateDistance($vector1, $vector2);
        $this->assertEquals(0.6206, $distance);

    }
}
