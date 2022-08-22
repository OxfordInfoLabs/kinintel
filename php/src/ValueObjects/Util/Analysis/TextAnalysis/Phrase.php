<?php

namespace Kinintel\ValueObjects\Util\Analysis\TextAnalysis;

class Phrase {

    /**
     * @var string
     */
    private $phrase;

    /**
     * @var integer
     */
    private $frequency;

    /**
     * @var integer
     */
    private $length;

    /**
     * @param string $phrase
     * @param int $frequency
     * @param int $length
     */
    public function __construct($phrase, $frequency, $length) {
        $this->phrase = $phrase;
        $this->frequency = $frequency;
        $this->length = $length;
    }


    /**
     * @return string
     */
    public function getPhrase() {
        return $this->phrase;
    }

    /**
     * @param string $phrase
     */
    public function setPhrase($phrase) {
        $this->phrase = $phrase;
    }

    /**
     * @return int
     */
    public function getFrequency() {
        return $this->frequency;
    }

    /**
     * @param int $frequency
     */
    public function setFrequency($frequency) {
        $this->frequency = $frequency;
    }

    /**
     * @return int
     */
    public function getLength() {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength($length) {
        $this->length = $length;
    }




}
