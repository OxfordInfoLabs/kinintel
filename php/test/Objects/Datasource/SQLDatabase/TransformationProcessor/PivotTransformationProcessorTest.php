<?php

namespace Kinintel\Test\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\PivotTransformationProcessor;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Pivot\PivotExpression;
use Kinintel\ValueObjects\Transformation\Pivot\PivotTransformation;

include_once "autoloader.php";

class PivotTransformationProcessorTest extends TestBase {

    public function testPivotTransformationCorrectlyCreatesSQL() {

        $pivotTransformation = new PivotTransformation(["name"], [
            new PivotExpression("qOne", "CASE WHEN [[question]] = 'Q1' THEN [[score]] ELSE null END"),
            new PivotExpression("qTwo", "CASE WHEN [[question]] = 'Q2' THEN [[score]] ELSE null END"),
            new PivotExpression("total", "SUM([[score]])")
        ]);

        $query = new SQLQuery('*', "my_table");

        $datasource = MockObjectProvider::mock(SQLDatabaseDatasource::class);
        $datasource->returnValue("getConfig", MockObjectProvider::mock(SQLDatabaseDatasourceConfig::class));
        $datasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());

        $transformationProcessor = new PivotTransformationProcessor();
        $query = $transformationProcessor->updateQuery($pivotTransformation, $query, [], $datasource);

        $this->assertEquals('SELECT "name", CASE WHEN "question" = ? THEN "score" ELSE null END "qOne", CASE WHEN "question" = ? THEN "score" ELSE null END "qTwo", SUM("score") "total" FROM my_table GROUP BY "name"', $query->getSQL());

    }

}