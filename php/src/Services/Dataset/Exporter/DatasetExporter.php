<?php


namespace Kinintel\Services\Dataset\Exporter;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\Validator;
use Kinikit\MVC\ContentSource\ContentSource;
use Kinintel\Exception\InvalidDatasourceExporterConfigException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;


/**
 * Dataset exporter
 *
 * @implementation json \Kinintel\Services\Dataset\Exporter\JSONDatasetExporter
 * @implementation sv \Kinintel\Services\Dataset\Exporter\SVDatasetExporter
 *
 */
abstract class DatasetExporter {

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ObjectBinder
     */
    private $objectBinder;

    /**
     * Construct with validator
     *
     * DatasetExporter constructor.
     * @param Validator $validator
     * @param ObjectBinder $objectBinder
     */
    public function __construct($validator, $objectBinder) {
        $this->validator = $validator;
        $this->objectBinder = $objectBinder;
    }

    /**
     * Class to use for configuration if applicable for this dataset
     *
     * @return string
     */
    abstract public function getConfigClass();

    /**
     * Get the file extension to use when streaming as download
     * @param DatasetExporterConfiguration $exportConfiguration
     *
     * @return string
     */
    abstract public function getDownloadFileExtension($exportConfiguration = null);


    /**
     * Only required method for a dataset exporter
     *
     * @param Dataset $dataset
     * @param DatasetExporterConfiguration $exportConfiguration
     *
     * @return ContentSource
     */
    abstract public function exportDataset($dataset, $exportConfiguration = null);


    /**
     * Validate the configuration
     *
     * @param DatasetExporterConfiguration $config
     */
    public function validateConfig($config) {

        if ($this->getConfigClass()) {

            $configClass = $this->getConfigClass();
            if (is_array($config)) {
                $config = $this->objectBinder->bindFromArray($config, $configClass);
            } else if (!is_a($config, $configClass)) {
                throw new InvalidDatasourceExporterConfigException([
                    "config" => ["type" => new FieldValidationError("type", "wrongtype", "Export config is of the wrong type for the exporter")]
                ]);
            }

            $validationErrors = $this->validator->validateObject($config);
            if (sizeof($validationErrors)) {
                throw new InvalidDatasourceExporterConfigException($validationErrors);
            }


            return $config;
        } else {
            return null;
        }
    }

}