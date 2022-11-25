<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\WebService;

use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\Validator;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\ValueObjects\Datasource\Configuration\DatasourceCompressionConfig;
use Kinintel\ValueObjects\Datasource\Configuration\FormattedResultDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration\CompressorConfiguration;

class WebserviceDataSourceConfig extends FormattedResultDatasourceConfig {

    use DatasourceCompressionConfig;

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
     * @var string[]
     */
    protected $headers;


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
     * If set, paging will be applied using parameters passed to the
     * webservice data fields instead of following the request in memory which is the default
     *
     * @var bool
     */
    protected $pagingViaParameters = false;

    /**
     * Name of file used if caching
     *
     * @var string
     */
    protected $cacheFileName = null;

    /**
     * Timeout for the cached file if caching used
     *
     * @var int
     */
    protected $cacheFileTimeout = null;

    /**
     *
     * @var bool
     */
    protected $encodeURLParameters = true;

    /**
     * WebserviceDataSourceConfig constructor.
     * @param string $url
     * @param string $method
     * @param string $payloadTemplate
     * @param string $resultFormat
     * @param mixed $resultFormatConfig
     * @param Field[] $columns
     * @param string $cacheFileName
     * @param int $cacheFileTimeout
     *
     */
    public function __construct($url = "", $method = Request::METHOD_GET, $payloadTemplate = null,
                                $resultFormat = "json", $resultFormatConfig = [], $columns = [], $cacheFileName = null, $cacheFileTimeout = null) {
        $this->url = $url;
        $this->method = $method;
        $this->payloadTemplate = $payloadTemplate;
        parent::__construct($resultFormat, $resultFormatConfig, $columns);
        $this->cacheFileName = $cacheFileName;
        $this->cacheFileTimeout = $cacheFileTimeout;
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
     * @return string[]
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @param string[] $headers
     */
    public function setHeaders($headers) {
        $this->headers = $headers;
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
     * @return bool
     */
    public function isPagingViaParameters() {
        return $this->pagingViaParameters;
    }

    /**
     * @param bool $pagingViaParameters
     */
    public function setPagingViaParameters($pagingViaParameters) {
        $this->pagingViaParameters = $pagingViaParameters;
    }

    /**
     * @return string
     */
    public function getCacheFileName() {
        return $this->cacheFileName;
    }

    /**
     * @param string $cacheFileName
     */
    public function setCacheFileName($cacheFileName) {
        $this->cacheFileName = $cacheFileName;
    }

    /**
     * @return int
     */
    public function getCacheFileTimeout() {
        return $this->cacheFileTimeout;
    }

    /**
     * @param int $cacheFileTimeout
     */
    public function setCacheFileTimeout($cacheFileTimeout) {
        $this->cacheFileTimeout = $cacheFileTimeout;
    }

    /**
     * @return bool
     */
    public function isEncodeURLParameters() {
        return $this->encodeURLParameters;
    }

    /**
     * @param bool $encodeURLParameters
     */
    public function setEncodeURLParameters($encodeURLParameters) {
        $this->encodeURLParameters = $encodeURLParameters;
    }


}
