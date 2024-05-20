<?php

namespace Kinintel\Objects\ResultFormatter;

use Kinikit\Core\Stream\ReadableStream;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\ValueObjects\Dataset\Field;

class XMLResultFormatter implements ResultFormatter {


    /**
     * Item wrapper tag assumed to contain the structured data for parsing
     *
     * @var string
     * @required
     */
    private $itemXPath;


    /**
     * @param string $itemXPath
     */
    public function __construct($itemXPath) {
        $this->itemXPath = $itemXPath;
    }


    /**
     * @return string
     */
    public function getItemXPath() {
        return $this->itemXPath;
    }

    /**
     * @param string $itemXPath
     */
    public function setItemXPath($itemXPath) {
        $this->itemXPath = $itemXPath;
    }


    /**
     * Format the supplied stream, return a data set
     *
     * @param ReadableStream $stream
     * @param Field[] $columns
     * @param int $limit
     * @param int $offset
     *
     * @return ArrayTabularDataset
     */
    public function format($stream, $columns = [], $limit = PHP_INT_MAX, $offset = 0) {

        // Grab full XML file as string
        $xmlString = $stream->getContents();

        // Convert to DOM document
        $xml = new \DOMDocument();
        $xml->loadXML($xmlString,LIBXML_NOERROR | LIBXML_NOWARNING);

        // Find the rows
        $xpath = new \DOMXPath($xml);
        $itemNodes = $xpath->query($this->itemXPath);

        // Loop through each item node, extract fields and values
        $fields = [];
        $data = [];
        foreach ($itemNodes as $node) {

            $dataItem = [];
            foreach ($node->childNodes as $valueNode) {
                if ($valueNode->nodeType != XML_TEXT_NODE) {

                    $fieldName = $valueNode->localName;
                    $value = $valueNode->nodeValue;

                    // Create field if required
                    if (!sizeof($data)) {
                        $fields[] = new Field($fieldName);
                    }

                    // Add the data item
                    $dataItem[$fieldName] = $value;
                }

            }

            // Add the data to the item
            $data[] = $dataItem;
        }


        return new ArrayTabularDataset($columns ?: $fields, $data);

    }
}