<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\Amazon;

use Kinintel\ValueObjects\Datasource\FormattedResultDatasourceConfig;

/**
 * Required config for Amazon S3
 *
 * Class AmazonS3DatasourceConfig
 * @package Kinintel\ValueObjects\Datasource\Amazon
 */
class AmazonS3DatasourceConfig extends FormattedResultDatasourceConfig {


    /**
     * The region where the S3 bucket is located
     *
     * @var string
     */
    private $region;

    /**
     * The bucket
     *
     * @var string
     */
    private $bucket;


    /**
     * The filename within the bucket
     *
     * @var string
     */
    private $filename;


    /**
     * AmazonS3DatasourceConfig constructor.
     *
     * @param string $region
     * @param string $bucket
     * @param string $filename
     */
    public function __construct($region, $bucket, $filename, $resultFormat = "json", $resultFormatConfig = []) {
        $this->region = $region;
        $this->bucket = $bucket;
        $this->filename = $filename;
        parent::__construct($resultFormat, $resultFormatConfig);
    }

    /**
     * @return string
     */
    public function getRegion() {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion($region) {
        $this->region = $region;
    }


    /**
     * @return string
     */
    public function getBucket() {
        return $this->bucket;
    }

    /**
     * @param string $bucket
     */
    public function setBucket($bucket) {
        $this->bucket = $bucket;
    }

    /**
     * @return string
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename) {
        $this->filename = $filename;
    }


}