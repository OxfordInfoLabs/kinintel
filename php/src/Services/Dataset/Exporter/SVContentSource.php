<?php


namespace Kinintel\Services\Dataset\Exporter;


use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\MVC\ContentSource\ContentSource;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\ValueObjects\Dataset\Exporter\SVDatasetExporterConfiguration;

class SVContentSource extends ContentSource {

    /**
     * @var TabularDataset
     */
    private $dataset;

    /**
     * @var SVDatasetExporterConfiguration
     */
    private $exportConfiguration;

    /**
     * SVContentSource constructor.
     * @param TabularDataset $dataset
     * @param SVDatasetExporterConfiguration $exportConfiguration
     */
    public function __construct($dataset, $exportConfiguration) {
        $this->dataset = $dataset;
        $this->exportConfiguration = $exportConfiguration;
    }

    /**
     * Get content type
     *
     * @return string
     */
    public function getContentType() {
        return "text/csv";
    }


    /**
     * Unknown content length
     *
     * @return null
     */
    public function getContentLength() {
        return null;
    }

    public function streamContent() {

        $out = fopen('php://output', 'w');
        $separator = $this->exportConfiguration->getSeparator();

        // If including a header row, write this
        if ($this->exportConfiguration->isIncludeHeaderRow()) {
            $columnNames = ObjectArrayUtils::getMemberValueArrayForObjects("name", $this->dataset->getColumns());
            fputcsv($out, $columnNames, $separator);
        }

        // Output as csv
        while ($item = $this->dataset->nextDataItem()) {
            $values = array_map(function ($value) {
                return is_array($value) || is_object($value) ? json_encode($value, JSON_INVALID_UTF8_IGNORE) : $value;
            }, array_values($item));
            fputcsv($out, $values, $separator);
        }
    }
}