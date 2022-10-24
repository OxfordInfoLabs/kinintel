<?php

namespace Kinintel\Objects\Datasource\Google;

use GuzzleHttp\Psr7\Stream;
use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Stream\StreamException;

class ReadOnlyGuzzleStream implements ReadableStream {


    /**
     * @var Stream
     */
    private $guzzleStream;

    /**
     * @var string
     */
    private $buffer = "";

    /**
     * @var bool
     */
    private $open = true;

    /**
     * Construct with guzzle stream
     *
     * @param Stream $guzzleStream
     */
    public function __construct($guzzleStream) {
        $this->guzzleStream = $guzzleStream;
    }


    public function read($length) {
        if (!$this->open) {
            throw new StreamException("The stream has been closed");
        }
        if ($this->guzzleStream->eof()) {
            throw new StreamException("Cannot read bytes as end of stream reached");
        }
        return $this->guzzleStream->read($length);

    }

    public function readLine() {

        $bufferExplode = explode("\n", $this->buffer);
        if (sizeof($bufferExplode) > 1) {
            $line = $bufferExplode[0];
            unset($bufferExplode[0]);

            $this->buffer = join("\n", $bufferExplode);
            return $line;
        }

        if ($this->guzzleStream->eof()) {
            $remains = $this->buffer;
            $this->buffer = "";
            return $remains;
        }

        $block = $this->guzzleStream->read(2048);
        $this->buffer .= $block;

        return $this->readLine();

    }

    public function readCSVLine($separator = ",", $enclosure = '"') {
        return str_getcsv($this->readLine(), $separator, $enclosure);
    }

    public function getContents() {
        return $this->guzzleStream->getContents();
    }

    public function isOpen() {
        return $this->open;
    }

    public function isEof() {
        return $this->guzzleStream->eof() && !strlen($this->buffer);
    }

    public function close() {
        $this->open = false;
        $this->guzzleStream->close();
    }
}