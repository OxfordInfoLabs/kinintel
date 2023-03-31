<?php


namespace Kinintel\Services\Dataset\Exporter;


use Kinikit\MVC\ContentSource\ContentSource;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\ValueObjects\Dataset\Exporter\SVDatasetExporterConfiguration;

class SVDatasetExporter extends DatasetExporter {

    /**
     * Get the config class for SV dataset export
     *
     * @return string|void
     */
    public function getConfigClass() {
        return SVDatasetExporterConfiguration::class;
    }


    /**
     * Get download file extension
     *
     * @param SVDatasetExporterConfiguration $exportConfiguration
     * @return string|void
     */
    public function getDownloadFileExtension($exportConfiguration = null) {
        return $exportConfiguration->getSeparator() == "\t" ? "tsv" : "csv";
    }

    /**
     * Export the dataset with a passed configuration
     *
     * @param TabularDataset $dataset
     * @param SVDatasetExporterConfiguration $exportConfiguration
     * @return ContentSource
     */
    public function exportDataset($dataset, $exportConfiguration = null) {

        if (!$exportConfiguration) {
            $exportConfiguration = new SVDatasetExporterConfiguration();
        }

        return new SVContentSource($dataset, $exportConfiguration);

    }
}