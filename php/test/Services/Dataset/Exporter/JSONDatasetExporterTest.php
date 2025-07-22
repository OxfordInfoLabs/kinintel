<?php


namespace Kinintel\Test\Services\Dataset\Exporter;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\MVC\ContentSource\StringContentSource;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Dataset\Exporter\JSONContentSource;
use Kinintel\Services\Dataset\Exporter\JSONDatasetExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class JSONDatasetExporterTest extends TestBase {

    public function testDatasetIsEvaluatedAndConvertedToJSON() {

        $jsonExporter = Container::instance()->get(JSONDatasetExporter::class);

        $data = [
            ["name" => "Bob", "age" => 33],
            ["name" => "Mary", "age" => 44],
            ["name" => "Joan", "age" => 55],
        ];

        $dataset = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], $data);


        $contentSource = $jsonExporter->exportDataset($dataset);
        $this->assertEquals(new JSONContentSource($data), $contentSource);


    }

}