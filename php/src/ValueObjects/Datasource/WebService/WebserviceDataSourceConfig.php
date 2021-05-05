<?php

namespace Kinintel\ValueObjects\Datasource\WebService;

use Kinikit\Core\HTTP\Request\Request;
use Kinintel\ValueObjects\Datasource\DatasourceConfig;

class WebserviceDataSourceConfig implements DatasourceConfig {

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
     * Parameters to pass to the web service
     *
     * @var array
     */
    protected $params = [];

    /**
     * WebserviceDataSourceConfig constructor.
     * @param string $url
     * @param string $method
     * @param array $params
     * @param string $payloadTemplate
     *
     */
    public function __construct($url = "", $method = Request::METHOD_GET, $params = [], $payloadTemplate = null) {
        $this->url = $url;
        $this->method = $method;
        $this->params = $params;
        $this->payloadTemplate = $payloadTemplate;
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
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams($params) {
        $this->params = $params;
    }


}