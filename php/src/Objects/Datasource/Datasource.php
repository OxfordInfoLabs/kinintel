<?php


namespace Kinintel\Objects\Datasource;

use Kinikit\Core\Validation\FieldValidationError;
use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\DatasourceConfig;
use Kinintel\ValueObjects\Transformation\Transformation;

/**
 *
 * @implementation webservice \Kinintel\Objects\Datasource\WebService\WebServiceDatasource
 * @implementation amazons3 \Kinintel\Objects\Datasource\Amazon\AmazonS3Datasource
 * @implementation sqldatabase \Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource
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
     *
     * @param Transformation $transformation
     * @return BaseDatasource
     */
    public function applyTransformation($transformation);


    /**
     * Materialise this datasource to a dataset.
     *
     * @return Dataset
     */
    public function materialise();


}