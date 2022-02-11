<?php

namespace Kinintel\Objects\Datasource\WebService;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\HTTPHeaderAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\QueryParameterAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;

/**
 * Generic web service data source - should be subclassed with format specific webservice classes.
 *
 * Class WebServiceDatasource
 * @package Kinintel\Objects\Datasource\WebService
 */
class WebServiceDatasource extends BaseDatasource {


    /**
     * @var HttpRequestDispatcher
     */
    private $dispatcher;

    /**
     * @var TemplateParser
     */
    private $templateParser;

    /**
     * @var PagingTransformation[]
     */
    private $pagingTransformations = [];


    /**
     * Extensible supported auth credentials
     *
     * @var array
     */
    private static $supportedAuthenticationCredentials = [
        BasicAuthenticationCredentials::class,
        QueryParameterAuthenticationCredentials::class,
        HTTPHeaderAuthenticationCredentials::class
    ];


    public function __construct($config = null, $authenticationCredentials = null, $validator = null) {

        // Construct parent
        parent::__construct($config, $authenticationCredentials, $validator);

        $this->dispatcher = Container::instance()->get(HttpRequestDispatcher::class);
        $this->templateParser = Container::instance()->get(TemplateParser::class);
    }

    /**
     * Define the config class in use for web service
     *
     * @return string
     */
    public function getConfigClass() {
        return WebserviceDataSourceConfig::class;
    }

    /**
     * Return the supported authentication credentials
     *
     * @return string[]
     */
    public function getSupportedCredentialClasses() {
        return self::$supportedAuthenticationCredentials;
    }

    /**
     * We allow guest web service calls
     *
     * @return false
     */
    public function isAuthenticationRequired() {
        return false;
    }


    /**
     * Get supported transformation classes
     *
     * @return string[]
     */
    public function getSupportedTransformationClasses() {
        return [PagingTransformation::class, ColumnsTransformation::class];
    }


    /**
     * Allow setting of dispatcher for testing
     *
     * @param HttpRequestDispatcher $dispatcher
     */
    public function setDispatcher($dispatcher) {
        $this->dispatcher = $dispatcher;
    }


    /**
     *
     *
     * @param Transformation $transformation
     * @param array $parameterValues
     * @return BaseDatasource
     */
    public function applyTransformation($transformation, $parameterValues = []) {

        if ($transformation instanceof PagingTransformation) {
            $this->pagingTransformations[] = $transformation;
        } else if ($transformation instanceof ColumnsTransformation) {
            $this->getConfig()->setColumns($transformation->getColumns());
        }

        return $this;

    }

    /**
     * Return materialised result set
     *
     * @param array $parameterValues
     * @return \Kinintel\ValueObjects\Dataset\Dataset
     */
    public function materialiseDataset($parameterValues = []) {


        if (!$this->getConfig()) {
            $this->setConfig(new WebserviceDataSourceConfig());
        }

        /**
         * @var WebserviceDataSourceConfig $config
         */
        $config = $this->getConfig();


        // Grab headers and params
        $headers = new Headers($config->getHeaders() ?? []);
        $payload = null;


        if ($config->getPayloadTemplate() && $config->getMethod() != Request::METHOD_GET) {
            $payload = $this->templateParser->parseTemplateText($config->getPayloadTemplate(), $parameterValues);
        }

        // Create a new HttpRequest for this request
        $url = $this->templateParser->parseTemplateText($config->getUrl(), $parameterValues);
        $request = new Request($url, $config->getMethod(), [], $payload, $headers);

         // Inject authentication if required
        if ($this->getAuthenticationCredentials()) {
            $request = $this->getAuthenticationCredentials()->processRequest($request);
        }

        // Process multiple times according to retry configuration if required
        $attempts = 0;
        do {

            // Dispatch the request and return a response
            $response = $this->dispatcher->dispatch($request);

            $attempts++;

        } while ($attempts <= $config->getMaxRetries() && in_array($response->getStatusCode(), $config->getRetryResponseCodes() ?? []));



        $offset = 0;
        $limit = PHP_INT_MAX;

        // Increment the offset and limit accordingly.
        foreach ($this->pagingTransformations as $pagingTransformation) {
            $offset += $pagingTransformation->getOffset();
            $limit = $pagingTransformation->getLimit();
        }

        $responseStream = $response->getStream();

        if ($this->getConfig()->getCompressionType()) {
            $compressor = Container::instance()->getInterfaceImplementation(Compressor::class, $this->getConfig()->getCompressionType());
            $responseStream = $compressor->uncompress($responseStream, $this->getConfig()->returnCompressionConfig());
        }

        // Materialise the web service result and return the result
        return $config->returnFormatter()->format($responseStream, $config->returnEvaluatedColumns($parameterValues), $limit, $offset);

    }


}