<?php

namespace Kinintel\Test\ValueObjects\Datasource\Update;

use Kinintel\Objects\FieldValidator\DateFieldValidator;
use Kinintel\Objects\FieldValidator\PickFromSourceFieldValidator;
use Kinintel\Objects\FieldValidator\RequiredFieldValidator;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateFieldValidatorConfig;

include_once "autoloader.php";

class DatasourceUpdateFieldValidatorConfigTest extends \PHPUnit\Framework\TestCase {

    public function testCanReturnValidatorInstanceForKeyAndConfig() {

        // Simple required one
        $config = new DatasourceUpdateFieldValidatorConfig("required", []);
        $this->assertEquals(new RequiredFieldValidator(), $config->returnFieldValidator());

        // Dates
        $config = new DatasourceUpdateFieldValidatorConfig("date", ["includeTime" => true]);
        $this->assertEquals(new DateFieldValidator(true), $config->returnFieldValidator());

        // Pick from
        $config = new DatasourceUpdateFieldValidatorConfig("pickfrom", ["valueFieldName" => "id", "datasourceInstanceKey" => "test"]);
        $this->assertEquals(new PickFromSourceFieldValidator("id", null, "test"), $config->returnFieldValidator());

    }

}