<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\WebScraper;

class WebScraperDatasourceConfig {

    /**
     * @var string
     */
    private $url;

    /**
     * Rows XPath selector for array of rows
     *
     * @var string
     */
    private $rowsXPath;


    /**
     * Columns with selectors
     *
     * @var FieldWithXPathSelector[]
     */
    private $columns;

    /**
     * @param string $url
     * @param string $rowsCSSSelector
     * @param FieldWithXPathSelector[] $columns
     */
    public function __construct($url, $rowsCSSSelector, $columns) {
        $this->url = $url;
        $this->rowsXPath = $rowsCSSSelector;
        $this->columns = $columns;
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
    public function getRowsXPath() {
        return $this->rowsXPath;
    }

    /**
     * @param string $rowsXPath
     */
    public function setRowsXPath($rowsXPath) {
        $this->rowsXPath = $rowsXPath;
    }

    /**
     * @return FieldWithXPathSelector[]
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @param FieldWithXPathSelector[] $columns
     */
    public function setColumns($columns) {
        $this->columns = $columns;
    }


}