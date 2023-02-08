<?php


namespace Kinintel\Objects\Datasource;

use Kinikit\Core\Validation\FieldValidationError;
use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\DatasourceConfig;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Transformation;

/**
 *
 * @implementation webservice \Kinintel\Objects\Datasource\WebService\WebServiceDatasource
 * @implementation ftp \Kinintel\Objects\Datasource\FTP\FTPDataSource
 * @implementation amazons3 \Kinintel\Objects\Datasource\Amazon\AmazonS3Datasource
 * @implementation sqldatabase \Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource
 * @implementation caching \Kinintel\Objects\Datasource\Caching\CachingDatasource
 * @implementation custom \Kinintel\Objects\Datasource\CustomDataSource
 * @implementation snapshot \Kinintel\Objects\Datasource\TabularSnapshotDataSource
 * @implementation document \Kinintel\Objects\Datasource\Document\DocumentDatasource
 * @implementation googlebucket \Kinintel\Objects\Datasource\Google\GoogleBucketFileDatasource
 * @implementation rsync \Kinintel\Objects\Datasource\RSync\RSyncDatasource
 */
interface Datasource {

    /**
     * Get the string name of the config class to use (if required) for this data source. Defaults to null
     * (not required)
     */
    public function getConfigClass();

    /**
     * Get an array of supported credential class strings
     */
    public function getSupportedCredentialClasses();


    /**
     * Default to requires authentication - generally the case
     *
     * @return bool
     */
    public function isAuthenticationRequired();


    /**
     * @return AuthenticationCredentials
     */
    public function getAuthenticationCredentials();


    /**
     * @param AuthenticationCredentials $authenticationCredentials
     */
    public function setAuthenticationCredentials($authenticationCredentials);


    /**
     * Set instance info from parent data source instance
     *
     * @param DatasourceInstance $instance
     * @return mixed
     */
    public function setInstanceInfo($instance);


    /**
     * @return DatasourceConfig
     */
    public function getConfig();

    /**
     * @param DatasourceConfig $config
     */
    public function setConfig($config);


    /**
     * Get an array of supported transformation classes for this datasource.
     *
     * @return string[]
     */
    public function getSupportedTransformationClasses();


    /**
     * Apply a transformation to this data source and return a new (or the same) data source.
     * Parameter values are passed if required by the transformation
     *
     * @param Transformation $transformation
     * @param array $parameterValues
     * @param null $pagingTransformation
     * @return BaseDatasource
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null);


    /**
     * Materialise this datasource to a dataset.  Parameter values are passed in
     * if required to materialise this datasource.
     *
     * @param array $parameterValues
     * @return Dataset
     */
    public function materialise($parameterValues = []);


}
