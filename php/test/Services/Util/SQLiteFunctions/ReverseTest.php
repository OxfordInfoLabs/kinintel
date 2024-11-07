<?php

namespace Kinintel\Test\Services\Util\SQLiteFunctions;

use Kinintel\Services\Util\SQLiteFunctions\Reverse;
use Kinintel\TestBase;
include_once "autoloader.php";

class ReverseTest extends TestBase {
    public function testReverse() {
        $reverseFunction = new Reverse();
        $this->assertSame("321-olleh-", $reverseFunction->execute("-hello-123"));
    }
}