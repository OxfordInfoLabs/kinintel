<?php

namespace Kinintel\ValueObjects\Datasource\Configuration;

use Kinintel\Objects\ResultFormatter\JSONResultFormatter;

include_once "autoloader.php";

class FormattedResultDatasourceConfigTest extends \PHPUnit\Framework\TestCase {


    public function testCanReturnResultFormatterForFormattedConfig() {


        // Test an object one
        $formattedResultConfig = new FormattedResultDatasourceConfig("json",new JSONResultFormatter("results", "", false));

        $this->assertEquals(new JSONResultFormatter("results", "", false), $formattedResultConfig->returnFormatter());

        // Test an unserialised array
        $formattedResultConfig = new FormattedResultDatasourceConfig("json", [
            "resultsOffsetPath" => "results.single",
            "singleResult" => true
        ]);

        $this->assertEquals(new JSONResultFormatter("results.single", "", true), $formattedResultConfig->returnFormatter());

    }

}