<?php


namespace Kinintel\Test\Services\Dataset\Exporter;

use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Dataset\Exporter\SVDatasetExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Exporter\SVDatasetExporterConfiguration;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class SVDatasetExporterTest extends TestBase {

    public function testCanExportDatasetAsSV() {

        $svExporter = new SVDatasetExporter();

        $data = [
            ["name" => "Bob", "age" => 33],
            ["name" => "Mary", "age" => 44],
            ["name" => "Joan", "age" => 55],
        ];


        $dataset = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], $data);

        // Default configuration
        ob_start();
        $svExporter->exportDataset($dataset);
        $results = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("name,age\nBob,33\nMary,44\nJoan,55\n", $results);


        // No header
        $dataset = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], $data);

        // Default configuration
        ob_start();
        $svExporter->exportDataset($dataset, new SVDatasetExporterConfiguration(false));
        $results = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("Bob,33\nMary,44\nJoan,55\n", $results);


        // Tab separator
        $dataset = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], $data);

        // Default configuration
        ob_start();
        $svExporter->exportDataset($dataset, new SVDatasetExporterConfiguration(false, "\t"));
        $results = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("Bob\t33\nMary\t44\nJoan\t55\n", $results);

    }

}