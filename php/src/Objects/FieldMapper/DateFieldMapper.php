<?php


namespace Kinintel\Objects\FieldMapper;


class DateFieldMapper implements FieldMapper {

    /**
     * Source date format in PHP date format
     *
     * @var string
     * @required
     */
    private $sourceFormat;


    /**
     * Target date format in PHP date format
     *
     * @var string
     * @required
     */
    private $targetFormat;

    /**
     * DateFieldMapper constructor.
     *
     * @param string $sourceFormat
     * @param string $targetFormat
     */
    public function __construct($sourceFormat = null, $targetFormat = null) {
        $this->sourceFormat = $sourceFormat;
        $this->targetFormat = $targetFormat;
    }

    /**
     * @return string
     */
    public function getSourceFormat() {
        return $this->sourceFormat;
    }

    /**
     * @param string $sourceFormat
     */
    public function setSourceFormat($sourceFormat) {
        $this->sourceFormat = $sourceFormat;
    }

    /**
     * @return string
     */
    public function getTargetFormat() {
        return $this->targetFormat;
    }

    /**
     * @param string $targetFormat
     */
    public function setTargetFormat($targetFormat) {
        $this->targetFormat = $targetFormat;
    }


    /**
     * Date field mapper implementation
     *
     * @param string $sourceValue
     * @return string
     */
    public function mapValue($sourceValue) {
        $date = date_create_from_format($this->getSourceFormat(), $sourceValue ?? "");
        if ($date) {
            return $date->format($this->getTargetFormat());
        } else {
            return null;
        }
    }
}