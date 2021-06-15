<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\WebService;

use Kinikit\Core\HTTP\Request\Request;
use Kinintel\ValueObjects\Datasource\FormattedResultDatasourceConfig;

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
     * WebserviceDataSourceConfig constructor.
     * @param string $url
     * @param string $method
     * @param string $payloadTemplate
     *
     */
    public function __construct($url = "", $method = Request::METHOD_GET, $payloadTemplate = null,
                                $resultFormat = "json", $resultFormatConfig = []) {
        $this->url = $url;
        $this->method = $method;
        $this->payloadTemplate = $payloadTemplate;
        parent::__construct($resultFormat, $resultFormatConfig);
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


}