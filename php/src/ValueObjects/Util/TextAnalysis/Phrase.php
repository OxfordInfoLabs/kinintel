<?php

namespace Kinintel\ValueObjects\Util\TextAnalysis;

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
     * @param string $phrase
     * @param int $frequency
     */
    public function __construct($phrase, $frequency) {
        $this->phrase = $phrase;
        $this->frequency = $frequency;
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




}
