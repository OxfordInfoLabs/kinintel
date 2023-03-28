<?php


namespace Kinintel\Test\Services\Dataset\Exporter;


use Kinintel\Services\Dataset\Exporter\DatasetExporter;

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