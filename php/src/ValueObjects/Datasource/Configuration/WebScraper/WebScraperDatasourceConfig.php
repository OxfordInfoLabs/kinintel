<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\WebScraper;

use Kinintel\ValueObjects\Datasource\Configuration\TabularResultsDatasourceConfig;

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
     * @var integer
     */
    private $firstRowOffset;

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
    public function __construct($url, $rowsCSSSelector, $firstRowOffset = 0, $columns = []) {
        $this->url = $url;
        $this->rowsXPath = $rowsCSSSelector;
        $this->firstRowOffset = $firstRowOffset;
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
     * @return int
     */
    public function getFirstRowOffset() {
        return $this->firstRowOffset;
    }

    /**
     * @param int $firstRowOffset
     * @return void
     */
    public function setFirstRowOffset($firstRowOffset) {
        $this->firstRowOffset = $firstRowOffset;
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