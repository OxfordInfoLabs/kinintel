<?php


namespace Kinintel\ValueObjects\Dataset;


class ExportDataset extends EvaluatedDataset {

    /**
     * @var string
     */
    private $exporterKey;


    /**
     * @var mixed
     */
    private $exporterConfiguration;

    /**
     * @return string
     */
    public function getExporterKey() {
        return $this->exporterKey;
    }

    /**
     * @param string $exporterKey
     */
    public function setExporterKey($exporterKey) {
        $this->exporterKey = $exporterKey;
    }

    /**
     * @return mixed
     */
    public function getExporterConfiguration() {
        return $this->exporterConfiguration;
    }

    /**
     * @param mixed $exporterConfiguration
     */
    public function setExporterConfiguration($exporterConfiguration) {
        $this->exporterConfiguration = $exporterConfiguration;
    }


}