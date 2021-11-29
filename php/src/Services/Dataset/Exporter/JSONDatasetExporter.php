<?php


namespace Kinintel\Services\Dataset\Exporter;

use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;

class JSONDatasetExporter implements DatasetExporter {

    /**
     * No config class required for JSON as simply relaying raw results
     *
     * @return null
     */
    public function getConfigClass() {
        return null;
    }

    /**
     * Export dataset in JSON format
     *
     * @param TabularDataset $dataset
     * @param DatasetExporterConfiguration $exportConfiguration
     */
    public function exportDataset($dataset, $exportConfiguration = null) {
        $allData = $dataset->getAllData();
        print(json_encode($allData));
    }
}