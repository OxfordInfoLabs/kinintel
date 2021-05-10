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
     * SVStreamTabularDataSet constructor.
     *
     * @param Field[] $columns
     * @param ReadableStream $stream
     * @param string $separator
     * @param string $enclosure
     */
    public function __construct($columns, $stream, $separator = ",", $enclosure = '"') {
        parent::__construct($columns);
        $this->stream = $stream;
        $this->separator = $separator;
        $this->enclosure = $enclosure;
    }


    /**
     * Read the next data item from the stream using the SV format.
     */
    public function nextDataItem() {

        $csvLine = $this->stream->readCSVLine($this->separator, $this->enclosure);
        $columns = $this->getColumns();

        // Only continue if we have some content
        if (trim($csvLine[0])) {

            while (sizeof($columns) < sizeof($csvLine)) {
                $columns[] = new Field("column" . (sizeof($columns) + 1));
                $this->setColumns($columns);
            }

            $dataItem = [];
            foreach ($csvLine as $index => $value) {
                $dataItem[$columns[$index]->getName()] = trim($value);
            }
            return $dataItem;
        } else {
            return null;
        }

    }
}