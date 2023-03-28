<?php


namespace Kinintel\Test\Services\Dataset\Exporter;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Exception\InvalidDatasourceExporterConfigException;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Exporter\SVDatasetExporterConfiguration;

include_once "autoloader.php";

class DatasetExporterTest extends TestBase {


    public function testValidateConfigValidatesConfigIncludingWrongClassTypes() {

        $testExporter = Container::instance()->get(TestExporter::class);

        // Invalid object type
        try {
            $testExporter->validateConfig(new SVDatasetExporterConfiguration());
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceExporterConfigException $e) {
            $this->assertTrue(true);
        }

        // Invalid object of correct type
        try {
            $testExporter->validateConfig(new TestExporterConfig());
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceExporterConfigException $e) {
            $this->assertTrue(true);
        }

        // Invalid array config
        try {
            $testExporter->validateConfig([]);
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceExporterConfigException $e) {
            $this->assertTrue(true);
        }

        // Valid array config converted to object
        $config = $testExporter->validateConfig(["param1" => "Hello", "param2" => 12]);
        $this->assertEquals(new TestExporterConfig("Hello", 12), $config);


    }

}