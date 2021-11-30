<?php


namespace Kinintel\Test\Services\Dataset\Exporter;


use Kinikit\MVC\ContentSource\ContentSource;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Services\Dataset\Exporter\DatasetExporter;
use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;

class TestExporter extends DatasetExporter {


    public function getConfigClass() {
        return TestExporterConfig::class;
    }

    public function exportDataset($dataset, $exportConfiguration = null) {
        // TODO: Implement exportDataset() method.
    }

    public function getDownloadFileExtension($exportConfiguration = null) {
        return "test";
    }
}