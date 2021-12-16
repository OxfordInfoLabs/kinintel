<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\WebService;

use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\Validator;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\ValueObjects\Datasource\FormattedResultDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration\CompressorConfiguration;

class WebserviceDataSourceConfig extends FormattedResultDatasourceConfig {

    /**
     * Main base URL for calling for this web service source
     *
     * @var string
     * @required
     */
    protected $url;


    /**
     * HTTP method (GET/POST etc) defaults to get for web sources
     *
     * @var string
     */
    protected $method;


    /**
     * A template written using installed Template Evaluator syntax evaluated with
     * supplied params to create the payload
     *
     * @var string
     */
    protected $payloadTemplate;


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
     * Response codes which if received will trigger a retry
     * for the retry interval specified separately.
     *
     * @var array
     */
    protected $retryResponseCodes = [];

    /**
     * Retry interval in seconds (defaults to 1)
     *
     * @var float
     */
    protected $retryInterval = 1;


    /**
     * Max retries (defaults to 10)
     *
     * @var int
     */
    protected $maxRetries = 10;

    /**
     * WebserviceDataSourceConfig constructor.
     * @param string $url
     * @param string $method
     * @param string $payloadTemplate
     * @param string $resultFormat
     * @param mixed $resultFormatConfig
     * @param Field[] $columns
     *
     */
    public function __construct($url = "", $method = Request::METHOD_GET, $payloadTemplate = null,
                                $resultFormat = "json", $resultFormatConfig = [], $columns = []) {
        $this->url = $url;
        $this->method = $method;
        $this->payloadTemplate = $payloadTemplate;
        parent::__construct($resultFormat, $resultFormatConfig, $columns);
    }


    /**
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method) {
        $this->method = $method;
    }


    /**
     * @return string
     */
    public function getPayloadTemplate() {
        return $this->payloadTemplate;
    }

    /**
     * @param string $payloadTemplate
     */
    public function setPayloadTemplate($payloadTemplate) {
        $this->payloadTemplate = $payloadTemplate;
    }

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
     * @return array
     */
    public function getRetryResponseCodes() {
        return $this->retryResponseCodes;
    }

    /**
     * @param array $retryResponseCodes
     */
    public function setRetryResponseCodes($retryResponseCodes) {
        $this->retryResponseCodes = $retryResponseCodes;
    }

    /**
     * @return float
     */
    public function getRetryInterval() {
        return $this->retryInterval;
    }

    /**
     * @param float $retryInterval
     */
    public function setRetryInterval($retryInterval) {
        $this->retryInterval = $retryInterval;
    }

    /**
     * @return int
     */
    public function getMaxRetries() {
        return $this->maxRetries;
    }

    /**
     * @param int $maxRetries
     */
    public function setMaxRetries($maxRetries) {
        $this->maxRetries = $maxRetries;
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