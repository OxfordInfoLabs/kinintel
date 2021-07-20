<?php


namespace Kinintel\Objects\FieldMapper;

use Kinintel\TestBase;

include_once "autoloader.php";

class DateFieldMapperTest extends TestBase {

    public function testCanConvertDateUsingSourceAndTargetFormat() {

        $dateFieldMapper = new DateFieldMapper("d/m/Y", "Y-m-d");

        // Check for valid mappings
        $this->assertEquals("1977-12-03", $dateFieldMapper->mapValue("03/12/1977"));
        $this->assertEquals("2020-12-01", $dateFieldMapper->mapValue("01/12/2020"));
        $this->assertEquals("1888-05-01", $dateFieldMapper->mapValue("01/05/1888"));

        // Check for invalid mappings
        $this->assertNull($dateFieldMapper->mapValue("2020-01-02"));
        $this->assertNull($dateFieldMapper->mapValue("BAD VALUE"));
        $this->assertNull($dateFieldMapper->mapValue(null));


    }

}