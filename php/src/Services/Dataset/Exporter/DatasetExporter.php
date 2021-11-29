<?php


namespace Kinintel\Services\Dataset\Exporter;


use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;


/**
 * Dataset exporter
 *
 * @implementation json \Kinintel\Services\Dataset\Exporter\JSONDatasetExporter
 * @implementation sv \Kinintel\Services\Dataset\Exporter\SVDatasetExporter
 *
 */
interface DatasetExporter {

    /**
     * Class to use for configuration if applicable for this dataset
     *
     * @return string
     */
    public function getConfigClass();

    /**
     * Only required method for a dataset exporter
     *
     * @param Dataset $dataset
     * @param DatasetExporterConfiguration $exportConfiguration
     */
    public function exportDataset($dataset, $exportConfiguration = null);

}