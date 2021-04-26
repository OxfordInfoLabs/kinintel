<?php

namespace Kinintel;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Tools\TestDataInstaller;

class TestBase extends \PHPUnit\Framework\TestCase {


    private static $run = false;

    public static function setUpBeforeClass(): void {

        if (!self::$run) {

            $testDataInstaller = Container::instance()->get(TestDataInstaller::class);
            $testDataInstaller->run(true, ["../src"]);
            self::$run = true;

        }
    }


}