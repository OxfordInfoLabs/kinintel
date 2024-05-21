<?php


namespace Kinintel\Test\Services\Util\SQLiteFunctions;

use Kinintel\Services\Util\SQLiteFunctions\Levenshtein;
use Kinintel\TestBase;

include_once "autoloader.php";

class LevenshteinTest extends TestBase {


    public function testCanUseLevenshteinFunction() {

        $levenshtein = new Levenshtein();

        $this->assertEquals(0, $levenshtein->execute("mark", "mark"));
        $this->assertEquals(1, $levenshtein->execute("mark", "mask"));
        $this->assertEquals(3, $levenshtein->execute("mark", "pink"));

    }


}