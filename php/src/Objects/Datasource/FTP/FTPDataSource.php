<?php


namespace Kinintel\Objects\Datasource\FTP;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\FTP\ReadOnlyFTPStream;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\ValueObjects\Authentication\FTP\FTPAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\Generic\UsernameAndPasswordAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\FTP\FTPDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class FTPDataSource extends BaseDatasource {


    /**
     * @var PagingTransformation[]
     */
    private $pagingTransformations = [];


    /**
     * Return the config class for the FTP data source
     *
     * @return string
     */
    public function getConfigClass() {
        return FTPDatasourceConfig::class;
    }


    /**
     * Return supported credential classes
     *
     * @return array
     */
    public function getSupportedCredentialClasses() {
        return [
            UsernameAndPasswordAuthenticationCredentials::class,
            FTPAuthenticationCredentials::class
        ];
    }


    /**
     * Return supported transformation classes
     *
     * @return string[]
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


    /**
     * Materialise this dataset
     *
     * @param array $parameterValues
     * @return Dataset|void
     */
    public function materialiseDataset($parameterValues = []) {

        /**
         * @var FTPDatasourceConfig $config
         */
        $config = $this->getConfig();

        /**
         * @var FTPAuthenticationCredentials $authCreds
         */
        $authCreds = $this->getAuthenticationCredentials();

        $offset = 0;
        $limit = PHP_INT_MAX;

        // Increment the offset and limit accordingly.
        foreach ($this->pagingTransformations as $pagingTransformation) {
            $offset += $pagingTransformation->getOffset();
            $limit = $pagingTransformation->getLimit();
        }

        // get an FTP stream for this data source
        $responseStream = new ReadOnlyFTPStream($config->getHostname(), $config->getFilePath(), $config->isSecure(),
            $authCreds->getUsername(), $authCreds->getPassword(), $authCreds->getPrivateKey());


        if ($this->getConfig()->getCompressionType()) {
            $compressor = Container::instance()->getInterfaceImplementation(Compressor::class, $this->getConfig()->getCompressionType());
            $responseStream = $compressor->uncompress($responseStream, $this->getConfig()->returnCompressionConfig());
        }

        // Materialise the web service result and return the result
        return $config->returnFormatter()->format($responseStream, $config->returnEvaluatedColumns($parameterValues), $limit, $offset);


    }


}