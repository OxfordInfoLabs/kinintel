<?php


namespace Kinintel\ValueObjects\Datasource\Configuration;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\Validator;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration\CompressorConfiguration;

trait DatasourceCompressionConfig {

    /**
     * Compression type if applicable - one of the compressor configuration values
     *
     * @var string
     */
    protected $compressionType;


    /**
     * Compression configuration - qualified by the compression type above
     *
     * @var mixed
     */
    protected $compressionConfig = [];


    /**
     * @return string
     */
    public function getCompressionType() {
        return $this->compressionType;
    }

    /**
     * @param string $compressionType
     */
    public function setCompressionType($compressionType) {
        $this->compressionType = $compressionType;
    }

    /**
     * @return mixed
     */
    public function getCompressionConfig() {
        return $this->compressionConfig;
    }

    /**
     * @param mixed $compressionConfig
     */
    public function setCompressionConfig($compressionConfig) {
        $this->compressionConfig = $compressionConfig;
    }


    /**
     * Implement custom validation for compressor type
     */
    public function validate() {

        $validationErrors = [];

        // If compression type, check that we have interface implementation to match
        if ($this->compressionType) {
            try {

                $compressionConfig = $this->returnCompressionConfig();

                /**
                 * @var Validator $validator
                 */
                $validator = Container::instance()->get(Validator::class);

                $configErrors = $validator->validateObject($compressionConfig);
                if (sizeof($configErrors)) {
                    $validationErrors["compressionConfig"] = $configErrors;
                }


            } catch (MissingInterfaceImplementationException $e) {
                $validationErrors["compressionType"] = [
                    "invalidtype" => new FieldValidationError("compressionType", "invalidtype", "The compression type '{$this->compressionType}' does not exists")
                ];
            }
        }


        return $validationErrors;

    }

    /**
     * Return compressor config
     *
     * @return CompressorConfiguration
     * @throws \Kinikit\Core\Binding\ObjectBindingException
     */
    public function returnCompressionConfig() {

        $compressor = Container::instance()->getInterfaceImplementation(Compressor::class, $this->compressionType);
        $configClass = $compressor->getConfigClass();

        $config = $this->getCompressionConfig();

        if (!($config instanceof CompressorConfiguration)) {
            /**
             * @var ObjectBinder $binder
             */
            $binder = Container::instance()->get(ObjectBinder::class);

            $config = $binder->bindFromArray($this->compressionConfig, $configClass);
        }

        return $config;
    }


}