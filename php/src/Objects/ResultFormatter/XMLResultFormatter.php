<?php

namespace Kinintel\Objects\ResultFormatter;

use Kinikit\Core\Stream\ReadableStream;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\WebScraper\FieldWithXPathSelector;
use Kinintel\ValueObjects\ResultFormatter\XPathTarget;

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
     * @param array $namespaces
     * @param XPathTarget[]|null $xpathTargets
     */
    public function __construct(
        string $itemXPath,
        private array $namespaces = [],
        private ?array $xpathTargets = null, // If this is null, we target all child elements of a row
        private $html = false
    ) {
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
        if ($this->html){
            $xml->loadHTML($xmlString,LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
                | LIBXML_NOERROR | LIBXML_NOWARNING
            );
        } else {
            $xml->loadXML($xmlString,LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
//                | LIBXML_NOERROR | LIBXML_NOWARNING
            );
        }
        $xpath = new \DOMXPath($xml);

        // Register namespaces - XML may parse jankily if you forget to do this!
        foreach ($this->namespaces as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }

        // Find the rows
        $itemNodes = $xpath->query($this->itemXPath);
        $itemNodes = array_slice([...$itemNodes], $offset); // Turn into an array

        // Loop through each item node, extract fields and values
        $fields = [];
        $data = [];
        if ($this->xpathTargets === null) {
            foreach ($itemNodes as $node) {
                if (count($data) >= $limit) {break;}
                $dataItem = [];
                foreach ($node->childNodes as $valueNodeList) {
                    if ($valueNodeList->nodeType != XML_TEXT_NODE) {

                        $fieldName = $valueNodeList->localName;
                        $valueNodeList = $valueNodeList->nodeValue;

                        // Create field if required
                        if (!sizeof($data)) {
                            $fields[] = new Field($fieldName);
                        }

                        // Add the data item
                        $dataItem[$fieldName] = $valueNodeList;
                    }

                }

                // Add the data to the item
                $data[] = $dataItem;
            }
        } else { // Using XPath targets
            foreach ($itemNodes as $node) {
                if (count($data) >= $limit) {break;}
                $dataItem = [];
                /** @var \DOMElement $node */
                foreach ($this->xpathTargets as $xpathTarget) {
                    $valueNodeList = $xpath->query($xpathTarget->xpath, $node);
                    if ($valueNodeList->length === 0) {
                        $value = null;
                    } else if (!$xpathTarget->multiple){
                        /** @var \DOMElement $value */
                        $valueNode = $valueNodeList->item(0);
                        $value = match($xpathTarget->attribute) {
                            null => $valueNode?->nodeValue,
                            FieldWithXPathSelector::ATTRIBUTE_TEXT => $valueNode?->textContent,
                            FieldWithXPathSelector::ATTRIBUTE_HTML => $xml->saveHTML($valueNode),
                            default => $valueNode?->getAttribute($xpathTarget->attribute),
                        };
                    } else {
                        $values = [];
                        foreach ($valueNodeList as $subValueNode) {
                            /** @var \DOMElement $subValueNode */
                            $values[] = match($xpathTarget->attribute) {
                                null => $subValueNode?->nodeValue,
                                FieldWithXPathSelector::ATTRIBUTE_TEXT => $subValueNode?->textContent,
                                FieldWithXPathSelector::ATTRIBUTE_HTML => $xml->saveHTML($subValueNode),
                                default => $subValueNode?->getAttribute($xpathTarget->attribute),
                            };
                        }
                        $value = $values;
                    }
                    $dataItem[$xpathTarget->name] = $value;
                }
                $data[] = $dataItem;
            }
            $fields = array_map(fn($target) => new Field($target->name), $this->xpathTargets);
        }

        return new ArrayTabularDataset($columns ?: $fields, $data);
    }
}