<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\MultiSortTransformationProcessor;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\InclusionCriteriaType;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\Sort;

include_once "autoloader.php";

class MultiSortTransformationProcessorTest extends \PHPUnit\Framework\TestCase {


    public function testSortsAreAppliedToSQLQueryAsExpectedAndEscapedAccordingly() {

        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $datasource->returnValue("returnDatabaseConnection", $databaseConnection);
        $databaseConnection->returnValue("escapeColumn", "'name'", ["name"]);
        $databaseConnection->returnValue("escapeColumn", "'dept'", ["dept"]);


        $sorts = [new Sort("name", "ASC"), new Sort("dept", "DESC")];
        $transformation = new MultiSortTransformation($sorts);

        $processor = new MultiSortTransformationProcessor();
        $sqlQuery = $processor->updateQuery($transformation, new SQLQuery("*", "test_data", [5, 6, 7]), [], $datasource);

        $this->assertEquals("SELECT * FROM test_data ORDER BY 'name' ASC, 'dept' DESC", $sqlQuery->getSQL());
        $this->assertEquals([5, 6, 7], $sqlQuery->getParameters());

    }

    public function testSortsAreExcludedFromSQLQueryIfInclusionCriteriaDefined() {

        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $datasource->returnValue("returnDatabaseConnection", $databaseConnection);
        $databaseConnection->returnValue("escapeColumn", "'name'", ["name"]);
        $databaseConnection->returnValue("escapeColumn", "'dept'", ["dept"]);



        $processor = new MultiSortTransformationProcessor();


        $sorts = [
            new Sort("name", "ASC", InclusionCriteriaType::ParameterPresent, "test1"),
            new Sort("dept", "DESC", InclusionCriteriaType::ParameterValue, "test2=hello")
        ];
        $transformation = new MultiSortTransformation($sorts);


        // No params should return unsorted query
        $sqlQuery = $processor->updateQuery($transformation, new SQLQuery("*", "test_data", [5, 6, 7]), [], $datasource);
        $this->assertEquals("SELECT * FROM test_data", $sqlQuery->getSQL());

        // First param set
        $sqlQuery = $processor->updateQuery($transformation, new SQLQuery("*", "test_data", [5, 6, 7]), ["test1" => "pink"], $datasource);
        $this->assertEquals("SELECT * FROM test_data ORDER BY 'name' ASC", $sqlQuery->getSQL());

        // Second param set but not to right value
        $sqlQuery = $processor->updateQuery($transformation, new SQLQuery("*", "test_data", [5, 6, 7]), ["test2" => "pink"], $datasource);
        $this->assertEquals("SELECT * FROM test_data", $sqlQuery->getSQL());

        // Second param set to right value
        $sqlQuery = $processor->updateQuery($transformation, new SQLQuery("*", "test_data", [5, 6, 7]), ["test2" => "hello"], $datasource);
        $this->assertEquals("SELECT * FROM test_data ORDER BY 'dept' DESC", $sqlQuery->getSQL());

        // Parameter not present
        $sorts = [
            new Sort("name", "ASC", InclusionCriteriaType::ParameterNotPresent, "test1"),
        ];

        $transformation = new MultiSortTransformation($sorts);

        // No params should return sorted query
        $sqlQuery = $processor->updateQuery($transformation, new SQLQuery("*", "test_data", [5, 6, 7]), [], $datasource);
        $this->assertEquals("SELECT * FROM test_data ORDER BY 'name' ASC", $sqlQuery->getSQL());

        $sqlQuery = $processor->updateQuery($transformation, new SQLQuery("*", "test_data", [5, 6, 7]), ["test1" => "set"], $datasource);
        $this->assertEquals("SELECT * FROM test_data", $sqlQuery->getSQL());


    }


}