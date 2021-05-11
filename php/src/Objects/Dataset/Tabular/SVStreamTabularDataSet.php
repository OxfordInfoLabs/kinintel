<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinikit\Core\Stream\ReadableStream;
use Kinintel\ValueObjects\Dataset\Field;

/**
 * General purpose tabular data set which receives a Separated Value stream
 * as input
 *
 * Class SVStreamTabularDataSet
 * @package Kinintel\Objects\Dataset\Tabular
 */
class SVStreamTabularDataSet extends TabularDataset {

    /**
     * @var ReadableStream
     */
    private $stream;

    /**
     * @var string
     */
    private $separator;

    /**
     * @var string
     */
    private $enclosure;

    /**
     * @var string
     */
    private $columns;


    /**
     * SVStreamTabularDataSet constructor.
     *
     * @param Field[] $columns
     * @param ReadableStream $stream
     * @param string $separator
     * @param string $enclosure
     */
    public function __construct($columns, $stream, $separator = ",", $enclosure = '"') {
        $this->columns = $columns;
        $this->stream = $stream;
        $this->separator = $separator;
        $this->enclosure = $enclosure;
    }


    /**
     * Return column array
     *
     * @return Field[]
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * Read the next data item from the stream using the SV format.
     */
    public function nextDataItem() {

        $csvLine = $this->stream->readCSVLine($this->separator, $this->enclosure);


        // Only continue if we have some content
        if (trim($csvLine[0])) {

            while (sizeof($this->columns) < sizeof($csvLine)) {
                $this->columns[] = new Field("column" . (sizeof($this->columns) + 1));

            }

            $dataItem = [];
            foreach ($csvLine as $index => $value) {
                $dataItem[$this->columns[$index]->getName()] = trim($value);
            }
            return $dataItem;
        } else {
            return null;
        }

    }


}