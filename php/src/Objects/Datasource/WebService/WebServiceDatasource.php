<?php

namespace Kinintel\Objects\Datasource\WebService;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Authentication\Generic\TokenExchangeAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\HTTPHeaderAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\QueryParameterAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\SubstitutionParameterAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
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
     * @var ParameterisedStringEvaluator
     */
    private $parameterisedStringEvaluator;

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
        HTTPHeaderAuthenticationCredentials::class,
        SubstitutionParameterAuthenticationCredentials::class,
        TokenExchangeAuthenticationCredentials::class
    ];


    public function __construct($config = null, $authenticationCredentials = null, $validator = null, $instanceKey = null, $instanceTitle = null, $instanceParameters = []) {

        // Construct parent
        parent::__construct($config, $authenticationCredentials, $validator, $instanceKey, $instanceTitle, $instanceParameters);

        $this->dispatcher = Container::instance()->get(HttpRequestDispatcher::class);
        $this->parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);
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
     * @param null $pagingTransformation
     * @return BaseDatasource
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {

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
     * @return Dataset
     */
    public function materialiseDataset($parameterValues = []) {

        if (!$this->getConfig()) {
            $this->setConfig(new WebserviceDataSourceConfig());
        }

        /**
         * @var WebserviceDataSourceConfig $config
         */
        $config = $this->getConfig();

        //if cache filename and timout in config, check for file in files.root/webservicecache/filename and timestamp.
        // If so, create alternative filestream (line 224) and use that
        // if not already exist, write stream to the file and return a filestream from this new file
        // for no caching do the usual
        $caching = false;
        $timeoutTime = 0;

        if ($config->getCacheFileName() && $config->getCacheFileTimeout()) {
            $caching = true;
            $cacheFileName = $config->getCacheFileName();
            $cacheDirectory = Configuration::readParameter("files.root") . "/webservice_caching/";
            if (file_exists($cacheDirectory . $cacheFileName)) {
                $timeoutTime = filemtime($cacheDirectory . $cacheFileName) + $config->getCacheFileTimeout();
            }
        }

        // Calculate offset and limits in case these need to be passed through
        $offset = 0;
        $limit = PHP_INT_MAX;

        // Increment the offset and limit accordingly.
        foreach ($this->pagingTransformations as $pagingTransformation) {

            // Handle non numeric transformations separately
            if (is_numeric($pagingTransformation->getOffset()) || $pagingTransformation->getOffset() === null)
                $offset += $pagingTransformation->getOffset();
            else
                $offset = $pagingTransformation->getOffset();

            $limit = $pagingTransformation->getLimit();
        }

        // Read from cache file if not timed out
        if ($caching && ($timeoutTime > date("U"))) {
            $responseStream = new ReadOnlyFileStream($cacheDirectory . $cacheFileName);
        } else {

            // Grab headers and params
            $headers = new Headers($config->getHeaders() ?? []);
            $payload = null;


            // If paging via parameters
            if ($config->isPagingViaParameters()) {
                $parameterValues["offset"] = $offset;
                $parameterValues["limit"] = $limit;
            }


            if ($config->getPayloadTemplate() && $config->getMethod() != Request::METHOD_GET) {
                $payload = $this->parameterisedStringEvaluator->evaluateString($config->getPayloadTemplate(), [], $parameterValues);
            }

            // Evaluate params
            $url = $this->parameterisedStringEvaluator->evaluateString($config->getUrl(), [], $parameterValues);



            if ($config->isEncodeURLParameters()) {
                // Encode URL to prevent transfer issues
                $explodedUrl = explode("?", $url, 2);
                $url = $explodedUrl[0];
                if (sizeof($explodedUrl) > 1) {
                    parse_str($explodedUrl[1], $queryParams);
                    $newParams = [];
                    foreach ($queryParams as $param => $value) {
                        $newParams[] = $param . "=" . urlencode($value);
                    }
                    $url .= "?" . join("&", $newParams);
                }
            }


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

            $responseStream = $response->getStream();

            if ($this->getConfig()->getCompressionType()) {
                $compressor = Container::instance()->getInterfaceImplementation(Compressor::class, $this->getConfig()->getCompressionType());
                $responseStream = $compressor->uncompress($responseStream, $this->getConfig()->returnCompressionConfig(), $parameterValues);
            }


            // Write to the cache file if caching, and create a stream from this new file
            if ($caching) {

                // Remove the old cache file - if there is one
                if (file_exists($cacheDirectory . $cacheFileName)) {
                    unlink($cacheDirectory . $cacheFileName);
                }

                if (!file_exists($cacheDirectory))
                    mkdir($cacheDirectory, 0777, true);

                touch($cacheDirectory . $cacheFileName);

                $file = fopen($cacheDirectory . $cacheFileName, "w");
                while (!$responseStream->isEof()) {
                    fputs($file, $responseStream->readLine() . "\n");
                }
                fclose($file);

                $responseStream = new ReadOnlyFileStream($cacheDirectory . $cacheFileName);

            }

        }

        // Materialise the web service result and return the result
        return $config->returnFormatter()->format(
            $responseStream,
            $config->returnEvaluatedColumns($parameterValues),
            $config->isPagingViaParameters() ? PHP_INT_MAX : $limit,
            $config->isPagingViaParameters() ? 0 : $offset
        );

    }


}
