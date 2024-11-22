<?php

namespace Kinintel\Objects\Datasource\WebScraper;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\WebScraper\FieldWithXPathSelector;
use Kinintel\ValueObjects\Datasource\Configuration\WebScraper\WebScraperDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;

/**
 * Web scraper datasource - used for obtaining structured data
 * from a web page, usually in tabular format.
 */
class WebScraperDatasource extends BaseDatasource {

    /**
     * @var HttpRequestDispatcher
     */
    private $httpRequestDispatcher;

    /**
     * @var ParameterisedStringEvaluator
     */
    private $parameterisedStringEvaluator;


    /**
     * @var PagingTransformation
     */
    private $pagingTransformation;

    /**
     * @param WebScraperDatasourceConfig $config
     */
    public function __construct($config = null) {
        parent::__construct($config);
        $this->httpRequestDispatcher = Container::instance()->get(HttpRequestDispatcher::class);
        $this->parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);
    }

    /**
     * @param HttpRequestDispatcher $httpRequestDispatcher
     */
    public function setHttpRequestDispatcher($httpRequestDispatcher) {
        $this->httpRequestDispatcher = $httpRequestDispatcher;
    }

    /**
     * Relax requirement for authentication
     *
     * @return false
     */
    public function isAuthenticationRequired() {
        return false;
    }

    /**
     * @return string
     */
    public function getConfigClass() {
        return WebScraperDatasourceConfig::class;
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
     * Return ourselves
     *
     * @param $transformation
     * @param $parameterValues
     * @param $pagingTransformation
     * @return $this|BaseDatasource
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {

        if ($transformation instanceof PagingTransformation) {
            $this->pagingTransformation = $transformation;
        }

        return $this;
    }


    /**
     * Materialise a dataset
     *
     * @param string[] $parameterValues
     * @return Dataset
     */
    public function materialiseDataset($parameterValues = []) {


        /**
         * @var WebScraperDatasourceConfig $config
         */
        $config = $this->getConfig();

        $url = $this->parameterisedStringEvaluator->evaluateString($config->getUrl(), [], $parameterValues);

        // Resolve URL
        $response = $this->httpRequestDispatcher->dispatch(new Request($url, Request::METHOD_GET, [], null, new Headers([
            Headers::USER_AGENT => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)"
        ])));


        // Generate HTML document
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($response->getBody(), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);


        // use the rows xpath to find the set of row nodes
        $xPath = new \DOMXPath($domDocument);
        $rows = $xPath->query($config->getRowsXPath());

        // Now gather column data
        $data = [];
        $rows = [...$rows];

        for ($i = $config->getFirstRowOffset(); $i < sizeof($rows); $i++) {

            $row = $rows[$i];

            $dataRow = [];
            foreach ($config->getColumns() as $column) {
                if ($column->getXpath()) {
                    $columnElements = $xPath->query($column->getXpath(), $row);
                    if ($columnElements->length > 0) {

                        /**
                         * @var \DOMElement $columnElement
                         */
                        $columnElement = $columnElements[0];

                        $value = null;
                        switch ($column->getAttribute()) {
                            case FieldWithXPathSelector::ATTRIBUTE_TEXT:
                                $value = $columnElement->textContent;
                                break;
                            case FieldWithXPathSelector::ATTRIBUTE_HTML:
                                $value = $domDocument->saveHTML($columnElement);
                                break;
                            default:
                                $value = $columnElement->getAttribute($column->getAttribute());
                                break;
                        }


                        $dataRow[$column->getName()] = $value;
                    }
                }

            }

            $data[] = $dataRow;
        }

        // If a paging transformation, slice and dice.
        if ($this->pagingTransformation) {
            $data = array_slice($data, $this->pagingTransformation->getOffset(), $this->pagingTransformation->getLimit());
        }

        // Simplify Fields
        $fields = array_map(function ($field) {
            return new Field($field->getName(), $field->getTitle(), $field->getValueExpression());
        }, $config->getColumns());

        return new ArrayTabularDataset($fields, $data);

    }

}