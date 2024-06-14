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

    private AuthenticationCredentialsService|MockObject $authenticationService;
    private SQLQueryDataProcessor|MockObject $processor;
    private DataProcessorInstance|MockObject $processorInstance;
    private SQLDatabaseCredentials|MockObject $credentials;
    private AuthenticationCredentialsInstance|MockObject $credentialsInstance;
    private DatabaseConnection|MockObject $databaseConnection;

    public function setUp(): void {
        $this->authenticationService = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsService::class);
        $this->processor = new SQLQueryDataProcessor($this->authenticationService);

        $this->processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $this->credentialsInstance = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsInstance::class);
        $this->authenticationService->returnValue("getCredentialsInstanceByKey", $this->credentialsInstance, ["testKey"]);
        $this->credentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $this->credentialsInstance->returnValue("returnCredentials", $this->credentials);
        $this->databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $this->credentials->returnValue("returnDatabaseConnection", $this->databaseConnection);
    }

    public function testValidationExceptionThrownIfWrongCredentialsType() {

        $query = "SELECT * FROM test";
        $processorConfig = new SQLQueryDataProcessorConfiguration(query: $query, authenticationCredentialsKey: "testKey");
        $this->processorInstance->returnValue("returnConfig", $processorConfig);

        $credentials = MockObjectProvider::instance()->getMockInstance(BasicAuthenticationCredentials::class);
        $this->credentialsInstance->returnValue("returnCredentials", $credentials);

        try {
            $this->processor->process($this->processorInstance);
            $this->fail("Should have thrown");
        } catch (InvalidDataProcessorConfigException $e) {
            $this->assertTrue(true);
        }

    }


    public function testCanIssueQueryToConfiguredDatabase() {

        $query = "SELECT * FROM test";
        $processorConfig = new SQLQueryDataProcessorConfiguration(query: $query, authenticationCredentialsKey: "testKey");
        $this->processorInstance->returnValue("returnConfig", $processorConfig);

        $this->processor->process($this->processorInstance);

        $this->assertTrue($this->databaseConnection->methodWasCalled("execute", [
            $query
        ]));
    }

    public function testCanEvaluateParameterisedQuery() {

        $query = "SELECT * FROM set_{{2_DAYS_AGO | dateConvert 'Y-m-d H:i:s' 'm'}}";
        $processorConfig = new SQLQueryDataProcessorConfiguration(query: $query, authenticationCredentialsKey: "testKey");
        $this->processorInstance->returnValue("returnConfig", $processorConfig);

        $this->processor->process($this->processorInstance);

        $month = new \DateTime();
        $month->sub(new \DateInterval("P2D"));
        $expectedQuery = "SELECT * FROM set_" . $month->format("m");

        $this->assertTrue($this->databaseConnection->methodWasCalled("execute", [
            $expectedQuery
        ]));
    }

    public function testCanExecuteMultipleQueries() {
        $this->assertEmpty($this->databaseConnection->getMethodCallHistory("execute"));

        $queries = [
            "SELECT * FROM test",
            "SELECT * FROM test2"
        ];
        $processorConfig = new SQLQueryDataProcessorConfiguration(queries: $queries, authenticationCredentialsKey: "testKey");
        $this->processorInstance->returnValue("returnConfig", $processorConfig);

        $this->processor->process($this->processorInstance);


        $this->assertTrue($this->databaseConnection->methodWasCalled("execute", [
            $queries[0]
        ]));
        $this->assertTrue($this->databaseConnection->methodWasCalled("execute", [
            $queries[1]
        ]));
    }

    public function testCanExecuteQueriesFromScriptFile() {
        $scriptFilepath = "Files/example.sql";
        $processorConfig = new SQLQueryDataProcessorConfiguration(scriptFilepath: $scriptFilepath, authenticationCredentialsKey: "testKey");
        $this->processorInstance->returnValue("returnConfig", $processorConfig);

        $this->processor->process($this->processorInstance);

        $expected = [
            "INSERT INTO table VALUES ('pete # ', 200)",
            "INSERT INTO table

VALUES
    (
        'sam -- ',
        404
    )"
        ];
        $this->assertEquals(
            array_map(fn($x) => [$x], $expected),
            $this->databaseConnection->getMethodCallHistory("execute"));
    }

    public function testScriptSanitisation(){
        $sqlScript = <<<EOF
-- This is an example SQL file with some code that'll be executed. Semicolon for luck! ; cool;

INSERT INTO table VALUES ('pete # ', 200); # Pete exists
INSERT INTO table
/**
  multiline string is here
 */
VALUES
    (
     'sam -- ',
     404
    ); -- Sam is simply a fantasy
EOF;
        $statements = SQLQueryDataProcessor::scriptToStatements($sqlScript);
        $expected = [
            "INSERT INTO table VALUES ('pete # ', 200)",
            "INSERT INTO table

VALUES
    (
     'sam -- ',
     404
    )"
        ];
        $this->assertEquals($expected, $statements);
    }
}