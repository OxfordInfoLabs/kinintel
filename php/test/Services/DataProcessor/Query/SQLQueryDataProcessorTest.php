<?php

namespace Kinintel\Test\Services\DataProcessor\Query;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Exception\InvalidDataProcessorConfigException;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\Services\DataProcessor\Query\SQLQueryDataProcessor;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
use Kinintel\ValueObjects\DataProcessor\Configuration\Query\SQLQueryDataProcessorConfiguration;
use PHPUnit\Framework\TestCase;


include_once "autoloader.php";

class SQLQueryDataProcessorTest extends TestCase {

    /**
     * @var MockObject
     */
    private $authenticationService;

    /**
     * @var SQLQueryDataProcessor
     */
    private $processor;

    public function setUp(): void {
        $this->authenticationService = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsService::class);
        $this->processor = new SQLQueryDataProcessor($this->authenticationService);
    }

    public function testValidationExceptionThrownIfWrongCredentialsType() {

        $query = "SELECT * FROM test";
        $processorConfig = new SQLQueryDataProcessorConfiguration($query, null, "testKey");
        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $processorConfig);
        $credentialsInstance = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsInstance::class);
        $this->authenticationService->returnValue("getCredentialsInstanceByKey", $credentialsInstance, ["testKey"]);
        $credentials = MockObjectProvider::instance()->getMockInstance(BasicAuthenticationCredentials::class);
        $credentialsInstance->returnValue("returnCredentials", $credentials);

        try {
            $this->processor->process($processorInstance);
            $this->fail("Should have thrown");
        } catch (InvalidDataProcessorConfigException $e) {
            $this->assertTrue(true);
        }

    }


    public function testCanIssueQueryToConfiguredDatabase() {

        $query = "SELECT * FROM test";
        $processorConfig = new SQLQueryDataProcessorConfiguration($query, null, "testKey");
        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $processorConfig);
        $credentialsInstance = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsInstance::class);
        $this->authenticationService->returnValue("getCredentialsInstanceByKey", $credentialsInstance, ["testKey"]);
        $credentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $credentialsInstance->returnValue("returnCredentials", $credentials);
        $databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $credentials->returnValue("returnDatabaseConnection", $databaseConnection);


        $this->processor->process($processorInstance);

        $this->assertTrue($databaseConnection->methodWasCalled("execute", [
            $query
        ]));
    }

    public function testCanEvaluateParameterisedQuery() {

        $query = "SELECT * FROM set_{{2_DAYS_AGO | dateConvert 'Y-m-d H:i:s' 'm'}}";
        $processorConfig = new SQLQueryDataProcessorConfiguration($query, null, "testKey");
        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $processorConfig);
        $credentialsInstance = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsInstance::class);
        $this->authenticationService->returnValue("getCredentialsInstanceByKey", $credentialsInstance, ["testKey"]);
        $credentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $credentialsInstance->returnValue("returnCredentials", $credentials);
        $databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $credentials->returnValue("returnDatabaseConnection", $databaseConnection);


        $this->processor->process($processorInstance);

        $month = new \DateTime();
        $month->sub(new \DateInterval("P2D"));
        $expectedQuery = "SELECT * FROM set_" . $month->format("m");

        $this->assertTrue($databaseConnection->methodWasCalled("execute", [
            $expectedQuery
        ]));
    }

    public function testCanExecuteMultipleQueries() {

        $queries = [
            "SELECT * FROM test",
            "SELECT * FROM test2"
        ];
        $processorConfig = new SQLQueryDataProcessorConfiguration(null, $queries, "testKey");
        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $processorConfig);
        $credentialsInstance = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsInstance::class);
        $this->authenticationService->returnValue("getCredentialsInstanceByKey", $credentialsInstance, ["testKey"]);
        $credentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $credentialsInstance->returnValue("returnCredentials", $credentials);
        $databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $credentials->returnValue("returnDatabaseConnection", $databaseConnection);


        $this->processor->process($processorInstance);

        $this->assertTrue($databaseConnection->methodWasCalled("execute", [
            $queries[0]
        ]));
        $this->assertTrue($databaseConnection->methodWasCalled("execute", [
            $queries[1]
        ]));
    }
}