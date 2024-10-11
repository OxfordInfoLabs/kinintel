<?php

namespace Kinintel\Test\Services\Util\SQLiteFunctions;


use Kinintel\Services\Util\SQLiteFunctions\Levenshtein;
use Kinintel\Services\Util\SQLiteFunctions\Regexp;
use Kinintel\TestBase;

include_once "autoloader.php";

class RegexpTest extends TestBase {

    public function testCanUseRegexpFunction() {

        $regexp = new Regexp();

        $this->assertEquals(1, $regexp->execute(".*a.*", "mark"));
        $this->assertEquals(0, $regexp->execute(".*z", "mark"));
        $this->assertEquals(1, $regexp->execute("mar.*", "mark"));

    }

}