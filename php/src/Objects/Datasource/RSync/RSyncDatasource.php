<?php

namespace Kinintel\Objects\Datasource\RSync;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\ValueObjects\Datasource\Configuration\RSync\RSyncDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class RSyncDatasource extends BaseDatasource {

    /**
     * @var PagingTransformation[]
     */
    private $pagingTransformations = [];

    /**
     * @return string
     */
    public function getConfigClass() {
        return RSyncDatasourceConfig::class;
    }

    /**
     * @return false
     */
    public function isAuthenticationRequired() {
        return false;
    }

    /**
     * @return array
     */
    public function getSupportedTransformationClasses() {
        return [PagingTransformation::class];
    }


    /**
     * Apply a permitted transformation
     *
     * @param Transformation $transformation
     * @param array $parameterValues
     * @param PagingTransformation $pagingTransformation
     * @return $this|BaseDatasource
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {
        if ($transformation instanceof PagingTransformation) {
            $this->pagingTransformations[] = $transformation;
        }
        return $this;
    }

    public function materialiseDataset($parameterValues = []) {

        /**
         * @var RSyncDatasourceConfig $config
         */
        $config = $this->getConfig();

        $offset = 0;
        $limit = PHP_INT_MAX;

        // Increment the offset and limit accordingly.
        foreach ($this->pagingTransformations as $pagingTransformation) {
            $offset += $pagingTransformation->getOffset();
            $limit = $pagingTransformation->getLimit();
        }

        $source = $config->getSource();

        $targetDirectory = Configuration::readParameter("files.root") . "/rsync";
        $targetPath = $targetDirectory . "/" . $this->getInstanceInfo()->getKey();

        if (!file_exists($targetDirectory))
            mkdir($targetDirectory, 0777, true);

        exec("rsync $source $targetPath");

        $responseStream = new ReadOnlyFileStream($targetPath);


        if ($this->getConfig()->getCompressionType()) {
            $compressor = Container::instance()->getInterfaceImplementation(Compressor::class, $this->getConfig()->getCompressionType());
            $responseStream = $compressor->uncompress($responseStream, $this->getConfig()->returnCompressionConfig(), $parameterValues);
        }

        return $config->returnFormatter()->format($responseStream, $config->returnEvaluatedColumns($parameterValues), $limit, $offset);
    }
}