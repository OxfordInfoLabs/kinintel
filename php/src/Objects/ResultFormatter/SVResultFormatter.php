<?php


namespace Kinintel\Objects\ResultFormatter;


use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\ValueObjects\Dataset\Field;

/**
 * Separated values result formatter
 *
 * Class SVResultFormatter
 *
 * @package Kinintel\Objects\ResultFormatter
 */
class SVResultFormatter implements ResultFormatter {

    /**
     * The field separator - defaults to ,
     *
     * @var string
     */
    private $separator;


    /**
     * The enclosure for quoted strings - defaults to "
     *
     * @var string
     */
    private $enclosure;


    /**
     * Boolean as to whether the first row is a header
     *
     * @var boolean
     */
    private $firstRowHeader;


    /**
     * @var string
     */
    private $compressionFormat;


    // Supported compression formats
    const COMPRESSION_FORMAT_NONE = "none";
    const COMPRESSION_FORMAT_ZIP = "zip";

    /**
     * SVResultFormatter constructor.
     *
     * @param string $separator
     * @param string $lineEnding
     * @param string $enclosure
     * @param string $compressionFormat
     */
    public function __construct($separator = ",", $enclosure = '"', $firstRowHeader = false, $compressionFormat = self::COMPRESSION_FORMAT_NONE) {
        $this->separator = $separator;
        $this->enclosure = $enclosure;
        $this->firstRowHeader = $firstRowHeader;
        $this->compressionFormat = $compressionFormat;
    }

    /**
     * @return string
     */
    public function getSeparator() {
        return $this->separator;
    }

    /**
     * @param string $separator
     */
    public function setSeparator($separator) {
        $this->separator = $separator;
    }


    /**
     * @return string
     */
    public function getEnclosure() {
        return $this->enclosure;
    }

    /**
     * @param string $enclosure
     */
    public function setEnclosure($enclosure) {
        $this->enclosure = $enclosure;
    }


    /**
     * @return string
     */
    public function getCompressionFormat() {
        return $this->compressionFormat;
    }

    /**
     * @param string $compressionFormat
     */
    public function setCompressionFormat($compressionFormat) {
        $this->compressionFormat = $compressionFormat;
    }


    /**
     * Format the value string
     *
     * @param ReadableStream $result
     * @param int $limit
     * @param int $offset
     * @return Dataset
     */
    public function format($stream, $limit = PHP_INT_MAX, $offset = 0) {

        $columns = [];

        if ($this->firstRowHeader) {
            $columns = $this->processHeaderRow($stream);
        }

        return new SVStreamTabularDataSet($columns, $stream, $this->separator, $this->enclosure, $limit, $offset);


    }

    /**
     * @param ReadableStream $stream
     * @return array
     */
    private function processHeaderRow($stream) {

        // Grab the line as items
        $csvLine = $stream->readCSVLine($this->separator, $this->enclosure);

        // Create fields from these
        $columns = [];
        foreach ($csvLine as $column) {
            // Expand out the title and the name
            $name = StringUtils::convertToCamelCase(trim($column));
            $columns[] = new Field($name);
        }

        return $columns;
    }

}