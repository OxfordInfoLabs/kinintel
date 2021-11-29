<?php


namespace Kinintel\Test\Services\Dataset\Exporter;

use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Dataset\Exporter\JSONDatasetExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class JSONDatasetExporterTest extends TestBase {

    public function testDatasetIsEvaluatedAndConvertedToJSON() {

        $jsonExporter = new JSONDatasetExporter();

        $data = [
            ["name" => "Bob", "age" => 33],
            ["name" => "Mary", "age" => 44],
            ["name" => "Joan", "age" => 55],
        ];

        $dataset = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], $data);

        ob_start();
        $jsonExporter->exportDataset($dataset);
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(json_encode($data), $result);


    }

}