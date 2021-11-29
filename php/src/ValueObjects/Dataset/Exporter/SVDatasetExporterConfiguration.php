<?php


namespace Kinintel\ValueObjects\Dataset\Exporter;


class SVDatasetExporterConfiguration implements DatasetExporterConfiguration {

    /**
     * Include the header row in export
     *
     * @var boolean
     */
    private $includeHeaderRow;


    /**
     * Separator character - defaults to ,
     *
     * @var string
     */
    private $separator = ",";

    /**
     * SVDatasetExporterConfiguration constructor.
     *
     * @param bool $includeHeaderRow
     * @param string $separator
     */
    public function __construct($includeHeaderRow = true, $separator = ",") {
        $this->includeHeaderRow = $includeHeaderRow;
        $this->separator = $separator;
    }


    /**
     * @return bool
     */
    public function isIncludeHeaderRow() {
        return $this->includeHeaderRow;
    }

    /**
     * @param bool $includeHeaderRow
     */
    public function setIncludeHeaderRow($includeHeaderRow) {
        $this->includeHeaderRow = $includeHeaderRow;
    }

    /**
     * @return string
     */
    public function getSeparator() {
        return $this->separator;
    }

    /**
     * @param string $separator
     */
    public function setSeparator($separator) {
        $this->separator = $separator;
    }


}