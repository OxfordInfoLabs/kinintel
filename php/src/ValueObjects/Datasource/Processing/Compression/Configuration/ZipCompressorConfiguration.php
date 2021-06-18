<?php


namespace Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration;


class ZipCompressorConfiguration implements CompressorConfiguration {

    /**
     * The target filename of the entry inside to stream once expanded.
     *
     * @var string
     * @required
     */
    private $entryFilename;

    /**
     * ZipCompressorConfiguration constructor.
     *
     * @param string $entryFilename
     */
    public function __construct($entryFilename) {
        $this->entryFilename = $entryFilename;
    }


    /**
     *
     * @return string
     */
    public function getEntryFilename() {
        return $this->entryFilename;
    }

    /**
     * @param string $entryFilename
     */
    public function setEntryFilename($entryFilename) {
        $this->entryFilename = $entryFilename;
    }


}