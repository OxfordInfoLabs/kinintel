<?php

namespace Kinintel\Services\DataProcessor\Query;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinintel\Exception\InvalidDataProcessorConfigException;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\Services\DataProcessor\BaseDataProcessor;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;
use Kinintel\ValueObjects\DataProcessor\Configuration\Query\SQLQueryDataProcessorConfiguration;

class SQLQueryDataProcessor extends BaseDataProcessor {

    /**
     * @var AuthenticationCredentialsService
     */
    private $authenticationService;

    /**
     * @param AuthenticationCredentialsService $authenticationService
     */
    public function __construct($authenticationService) {
        $this->authenticationService = $authenticationService;
    }

    /**
     * @return string|void
     */
    public function getConfigClass() {
        return SQLQueryDataProcessorConfiguration::class;
    }

    /**
     * @param DataProcessorInstance $instance
     * @return void
     * @throws InvalidDataProcessorConfigException
     * @throws \Kinikit\Persistence\Database\Exception\SQLException
     * @throws \Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException
     */
    public function process($instance) {

        /**
         * @var SQLQueryDataProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        /**
         * @var AuthenticationCredentialsInstance $credentialsInstance
         */
        $credentialsInstance = $this->authenticationService->getCredentialsInstanceByKey($config->getAuthenticationCredentialsKey());

        // Get the credentials object and confirm it is a SQL database object
        $credentials = $credentialsInstance->returnCredentials();

        if (!($credentials instanceof SQLDatabaseCredentials)) {
            throw new InvalidDataProcessorConfigException(["authenticationCredentialsKey" => [
                "wrongType" => new FieldValidationError("authenticationCredentialsKey", "wrongType", "The credentials supplied were of the wrong type - must be SQL Database Credentials")
            ]]);
        }

        $databaseConnection = $credentials->returnDatabaseConnection();

        /**
         * @var ParameterisedStringEvaluator $parameterisedStringEvaluator
         */
        $parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);

        if ($config->getQuery()) {
            $query = $parameterisedStringEvaluator->evaluateString($config->getQuery(), [], []);

            $databaseConnection->execute($query);
        } else {
            foreach ($config->getQueries() as $query) {
                $query = $parameterisedStringEvaluator->evaluateString($query, [], []);

                $databaseConnection->execute($query);
            }
        }

    }

    public function onInstanceDelete($instance) {

    }

}