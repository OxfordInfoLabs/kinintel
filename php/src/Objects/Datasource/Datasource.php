<?php

namespace Kinintel\Objects\Datasource;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\Validator;
use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Datasource\DatasourceConfig;
use Kinintel\ValueObjects\Query\Query;

/**
 * Data source - these provide raw access to data using query objects
 *
 * @implementation json \Kinintel\Objects\Datasource\WebService\JSONWebServiceDatasource
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
    public function __construct($config = null, $authenticationCredentials = null) {
        $this->validator = Container::instance()->get(Validator::class);
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
            if (!in_array(get_class($authenticationCredentials), $this->getSupportedCredentialClasses())) {
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
     * Apply a query to this data source and return a new (or the same) data source.
     * This is primarily designed to facilitate query chaining using a MultiQuery
     *
     * @param Query $query
     * @return Datasource
     */
    public abstract function applyQuery($query);


    /**
     * Materialise this data source (after any queries have been applied)
     * and return a Dataset
     *
     * @return Dataset
     */
    public abstract function materialise();


}