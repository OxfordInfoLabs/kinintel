<?php

namespace Kinintel\Objects\Datasource\RSync;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinintel\Exception\RSyncException;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\ValueObjects\Authentication\Generic\UsernameAndPasswordAuthenticationCredentials;
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

    public function getSupportedCredentialClasses() {
        return [UsernameAndPasswordAuthenticationCredentials::class];
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

        if ($this->getAuthenticationCredentials()) {
            /**
             * @var UsernameAndPasswordAuthenticationCredentials $credentials
             */
            $credentials = $this->getAuthenticationCredentials();
            $command = 'sshpass -p "' . $credentials->getPassword() . '" rsync ' . ($config->getRsyncFlags() ?? "") . " " . $credentials->getUsername() . "@$source $targetPath";
        } else {
            $command = "rsync " . ($config->getRsyncFlags() ?? "") . " $source $targetPath";
        }

        exec($command, $output, $resultCode);

        if ($resultCode != 0) {
            throw new RSyncException("RSync failed from $source to $targetPath.", $resultCode);
        }

        $responseStream = new ReadOnlyFileStream($targetPath);

        if ($this->getConfig()->getCompressionType()) {
            $compressor = Container::instance()->getInterfaceImplementation(Compressor::class, $this->getConfig()->getCompressionType());
            $responseStream = $compressor->uncompress($responseStream, $this->getConfig()->returnCompressionConfig(), $parameterValues);
        }

        return $config->returnFormatter()->format($responseStream, $config->returnEvaluatedColumns($parameterValues), $limit, $offset);
    }
}