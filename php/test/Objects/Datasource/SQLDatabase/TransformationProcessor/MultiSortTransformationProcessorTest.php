<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\MultiSortTransformationProcessor;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
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


}