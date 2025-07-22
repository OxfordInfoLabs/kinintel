<?php


namespace Kinintel\Services\Dataset\Exporter;

use Kinikit\Core\Exception\DebugException;
use Kinikit\MVC\ContentSource\ContentSource;
use Kinikit\MVC\ContentSource\StringContentSource;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;

class JSONDatasetExporter extends DatasetExporter {

    /**
     * No config class required for JSON as simply relaying raw results
     *
     * @return null
     */
    public function getConfigClass() {
        return null;
    }

    /**
     * Get download file extension
     *
     * @param DatasetExporterConfiguration $exportConfiguration
     * @return string|void
     */
    public function getDownloadFileExtension($exportConfiguration = null) {
        return "json";
    }


    /**
     * Export dataset in JSON format
     *
     * @param TabularDataset $dataset
     * @param DatasetExporterConfiguration $exportConfiguration
     *
     * @return ContentSource
     */
    public function exportDataset($dataset, $exportConfiguration = null) {
        $allData = $dataset->getAllData();
        return new JSONContentSource($allData);
    }


}