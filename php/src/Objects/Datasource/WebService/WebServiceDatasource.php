<?php

namespace Kinintel\Objects\Datasource\WebService;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Template\TemplateParser;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\QueryParameterAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
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
        QueryParameterAuthenticationCredentials::class
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
        return [PagingTransformation::class];
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

        // Grab headers and params
        $headers = new Headers();
        $payload = null;

        if (!$this->getConfig()) {
            $this->setConfig(new WebserviceDataSourceConfig());
        }

        /**
         * @var WebserviceDataSourceConfig $config
         */
        $config = $this->getConfig();

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


        // Dispatch the request and return a response
        $response = $this->dispatcher->dispatch($request);

        $offset = 0;
        $limit = PHP_INT_MAX;

        // Increment the offset and limit accordingly.
        foreach ($this->pagingTransformations as $pagingTransformation) {
            $offset += $pagingTransformation->getOffset();
            $limit = $pagingTransformation->getLimit();
        }

        // Materialise the web service result and return the result
        return $config->returnFormatter()->format($response->getStream(), $config->returnEvaluatedColumns($parameterValues), $limit, $offset);

    }


}