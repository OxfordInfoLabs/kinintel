<?php


namespace Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration;


class ZipCompressorConfiguration implements CompressorConfiguration {

    /**
     * The target filename of the entry inside to stream once expanded.
     *
     * @var string
     * @requiredEither entryFilenames
     */
    private $entryFilename;


    /**
     * The target filenames of entries inside to stream as a combined stream.
     * This should be used instead of entryFilename if more than one file is to be
     * included.
     *
     * @var string[]
     */
    private $entryFilenames;

    /**
     * ZipCompressorConfiguration constructor.
     *
     * @param string $entryFilename
     */
    public function __construct($entryFilename = null, $entryFilenames = null) {
        $this->entryFilename = $entryFilename;
        $this->entryFilenames = $entryFilenames;
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

    /**
     * @return string[]
     */
    public function getEntryFilenames() {
        return $this->entryFilenames;
    }

    /**
     * @param string[] $entryFilenames
     */
    public function setEntryFilenames($entryFilenames) {
        $this->entryFilenames = $entryFilenames;
    }


}