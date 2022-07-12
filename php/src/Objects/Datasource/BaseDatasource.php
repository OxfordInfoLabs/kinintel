<?php

namespace Kinintel\Objects\Datasource;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Core\Validation\Validator;
use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\DatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceInstanceInfo;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Transformation;

/**
 * Data source - these provide raw access to data using query objects
 *
 *
 */
abstract class BaseDatasource implements Datasource {

    /**
     * Key for the instance which created this data source if relevant
     *
     * @var DatasourceInstanceInfo
     */
    private $instanceInfo;


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
    public function __construct($config = null, $authenticationCredentials = null, $validator = null, $instanceInfo = null) {
        $this->validator = $validator ? $validator : Container::instance()->get(Validator::class);
        if ($config)
            $this->setConfig($config);

        if ($authenticationCredentials) {
            $this->setAuthenticationCredentials($authenticationCredentials);
        }

        $this->instanceInfo = $instanceInfo;


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
     * Implement set instance info
     *
     * @param DatasourceInstance $instance
     * @return mixed|void
     */
    public function setInstanceInfo($instance) {
        $this->instanceInfo = new DatasourceInstanceInfo($instance);
    }

    /**
     * @return DatasourceInstanceInfo
     */
    public function getInstanceInfo() {
        return $this->instanceInfo;
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

            } else if ($this->getConfigClass() !== get_class($config) && !is_subclass_of($config, $this->getConfigClass())) {
                throw new InvalidDatasourceConfigException(["config" => [
                    "wrongtype" =>
                        new FieldValidationError("config", "wrongtype", "Config supplied is of wrong type for data source")
                ]]);
            } else {
                $validationErrors = $this->validator->validateObject($config);
                if (is_array($validationErrors) && sizeof($validationErrors)) {
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
                if (is_array($validationErrors) && sizeof($validationErrors)) {
                    throw new InvalidDatasourceAuthenticationCredentialsException($validationErrors);
                }
            }
        }

        $this->authenticationCredentials = $authenticationCredentials;
    }


    /**
     * Materialise the dataset having first checked any validation stuff
     * @param array $parameterValues
     *
     * @return Dataset
     */
    public function materialise($parameterValues = []) {

        if ($this->isAuthenticationRequired() && !$this->authenticationCredentials) {
            throw new MissingDatasourceAuthenticationCredentialsException(["authenticationCredentials" => [
                "required" =>
                    new FieldValidationError("authenticationCredentials", "required", "Authentication credentials are required for data source")
            ]]);
        }

        return $this->materialiseDataset($parameterValues);
    }

    /**
     * Materialise this data source (after any transformations have been applied)
     * and return a Dataset
     *
     * @param array $parameterValues
     * @return Dataset
     */
    public abstract function materialiseDataset($parameterValues = []);


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