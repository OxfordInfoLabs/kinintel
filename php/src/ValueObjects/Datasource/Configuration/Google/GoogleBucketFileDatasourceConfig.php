<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\Google;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Datasource\Configuration\FormattedResultDatasourceConfig;

class GoogleBucketFileDatasourceConfig extends FormattedResultDatasourceConfig {

    /**
     * @var string
     * @required
     */
    private $bucket;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var string
     *
     * @requiredEither folder
     */
    private $filePath;

    /**
     * @var ParameterisedStringEvaluator $parameterisedStringEvaluator
     */
    private $parameterisedStringEvaluator;


    /**
     * @param string $bucket
     * @param string $folder
     * @param string $filePath
     */
    public function __construct($bucket, $folder, $filePath) {
        $this->parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);
        $this->bucket = $this->parameterisedStringEvaluator->evaluateString($bucket, [], []);
        $this->folder = $this->parameterisedStringEvaluator->evaluateString($folder, [], []);
        $this->filePath = $this->parameterisedStringEvaluator->evaluateString($filePath, [], []);
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
    public function getFolder() {
        return $this->folder;
    }

    /**
     * @param string $folder
     */
    public function setFolder($folder) {
        $this->folder = $folder;
    }

    /**
     * @return string
     */
    public function getFilePath() {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath) {
        $this->filePath = $filePath;
    }


}