<?php


namespace Kinintel\Services\Dataset\Exporter;


use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;
use Kinintel\ValueObjects\Dataset\Exporter\SVDatasetExporterConfiguration;

class SVDatasetExporter implements DatasetExporter {

    /**
     * Get the config class for SV dataset export
     *
     * @return string|void
     */
    public function getConfigClass() {
        return SVDatasetExporterConfiguration::class;
    }

    /**
     * Export the dataset with a passed configuration
     *
     * @param TabularDataset $dataset
     * @param SVDatasetExporterConfiguration $exportConfiguration
     */
    public function exportDataset($dataset, $exportConfiguration = null) {
        if (!$exportConfiguration) {
            $exportConfiguration = new SVDatasetExporterConfiguration();
        }

        $out = fopen('php://output', 'w');
        $separator = $exportConfiguration->getSeparator();

        // If including a header row, write this
        if ($exportConfiguration->isIncludeHeaderRow()) {
            $columnNames = ObjectArrayUtils::getMemberValueArrayForObjects("name", $dataset->getColumns());
            fputcsv($out, $columnNames, $separator);
        }

        // Output as csv
        while ($item = $dataset->nextDataItem()) {
            fputcsv($out, array_values($item), $separator);
        }

    }
}