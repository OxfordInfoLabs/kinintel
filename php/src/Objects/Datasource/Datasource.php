<?php

namespace Kinintel\Objects\Datasource;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\Validator;
use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\DatasourceConfig;
use Kinintel\ValueObjects\Transformation\Transformation;

/**
 * Data source - these provide raw access to data using query objects
 *
 * @implementation webservice \Kinintel\Objects\Datasource\WebService\WebServiceDatasource
 * @implementation amazons3 \Kinintel\Objects\Datasource\Amazon\AmazonS3Datasource
 *
 */
abstract class Datasource {

    /**
     * Configuration for a data source
     *
     * @var DatasourceConfig
     */
    private $config;

    /**
     * @var AuthenticationCredentials
     */
    private $authenticationCredentials;


    /**
     * @var Validator
     */
    private $validator;


    /**
     * Datasource constructor.
     *
     * @param DatasourceConfig $config
     * @param AuthenticationCredentials $authenticationCredentials
     */
    public function __construct($config = null, $authenticationCredentials = null, $validator = null) {
        $this->validator = $validator ? $validator : Container::instance()->get(Validator::class);
        if ($config)
            $this->setConfig($config);

        if ($authenticationCredentials) {
            $this->setAuthenticationCredentials($authenticationCredentials);
        }


    }


    /**
     * Get the string name of the config class to use (if required) for this data source. Defaults to null
     * (not required)
     */
    public function getConfigClass() {
        return null;
    }


    /**
     * Get an array of supported credential class strings
     */
    public function getSupportedCredentialClasses() {
        return [];
    }

    /**
     * Default to requires authentication - generally the case
     *
     * @return bool
     */
    public function isAuthenticationRequired() {
        return true;
    }


    /**
     * @return DatasourceConfig
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param DatasourceConfig $config
     */
    public function setConfig($config) {

        // If a config supplied where no config class throw an exception
        if ($config) {
            if (!$this->getConfigClass()) {
                throw new InvalidDatasourceConfigException(["config" => [
                    "wrongtype" =>
                        new FieldValidationError("config", "wrongtype", "Config supplied to data source when none is required")

                ]]);
            } else if ($this->getConfigClass() !== get_class($config)) {
                throw new InvalidDatasourceConfigException(["config" => [
                    "wrongtype" =>
                        new FieldValidationError("config", "wrongtype", "Config supplied is of wrong type for data source")
                ]]);
            } else {
                $validationErrors = $this->validator->validateObject($config);
                if (sizeof($validationErrors)) {
                    throw new InvalidDatasourceConfigException($validationErrors);
                }
            }
        }

        $this->config = $config;
    }

    /**
     * @return AuthenticationCredentials
     */
    public function getAuthenticationCredentials() {
        return $this->authenticationCredentials;
    }

    /**
     * @param AuthenticationCredentials $authenticationCredentials
     */
    public function setAuthenticationCredentials($authenticationCredentials) {
        // If credentials supplied check for validity
        if ($authenticationCredentials) {
            if (!$this->authenticationClassMatchesSupported($authenticationCredentials)) {
                throw new InvalidDatasourceAuthenticationCredentialsException(["authenticationCredentials" => [
                    "wrongtype" =>
                        new FieldValidationError("authenticationCredentials", "wrongtype", "Authentication credentials supplied are of wrong type for data source")
                ]]);
            } else {

                $validationErrors = $this->validator->validateObject($authenticationCredentials);
                if (sizeof($validationErrors)) {
                    throw new InvalidDatasourceAuthenticationCredentialsException($validationErrors);
                }
            }
        }

        $this->authenticationCredentials = $authenticationCredentials;
    }


    /**
     * Materialise the dataset having first checked any validation stuff
     */
    public function materialise() {

        if ($this->isAuthenticationRequired() && !$this->authenticationCredentials) {
            throw new MissingDatasourceAuthenticationCredentialsException(["authenticationCredentials" => [
                "required" =>
                    new FieldValidationError("authenticationCredentials", "required", "Authentication credentials are required for data source")
            ]]);
        }

        return $this->materialiseDataset();
    }


    /**
     * Apply a transformation to this data source and return a new (or the same) data source.
     * This is primarily designed to facilitate query chaining using a MultiQuery
     *
     * @param Transformation $transformation
     * @return Datasource
     */
    public abstract function applyTransformation($transformation);


    /**
     * Materialise this data source (after any transformations have been applied)
     * and return a Dataset
     *
     * @return Dataset
     */
    public abstract function materialiseDataset();


    // Check whether the configured authentication class matches including subclasses
    private function authenticationClassMatchesSupported($authenticationObject) {

        $targetClass = get_class($authenticationObject);

        // Check supported classes
        foreach ($this->getSupportedCredentialClasses() as $supportedCredentialClass) {
            if ($supportedCredentialClass == $targetClass)
                return true;

            if (is_subclass_of($targetClass, $supportedCredentialClass))
                return true;
        }


        return false;
    }


}