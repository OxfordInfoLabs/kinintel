<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\BaseDatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Controllers\Account\Datasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SummariseTransformationProcessor;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseExpression;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;

include_once "autoloader.php";

class SummariseTransformationProcessorTest extends \PHPUnit\Framework\TestCase {

    public function testSummariseTransformationCorrectlyAppliesSelectAndGroupByClauseForBuiltInExpressions() {

        // COUNT(*)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_COUNT)]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSource->returnValue("getConfig", MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class));
        $dataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], $dataSource);

        $this->assertEquals("SELECT category, dept, COUNT(*) FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept", $query->getSQL());

        // SUM(total)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_SUM, "total")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], $dataSource);

        $this->assertEquals("SELECT category, dept, SUM(\"total\") FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept", $query->getSQL());


        // MAX(total)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MAX, "total")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], $dataSource);

        $this->assertEquals("SELECT category, dept, MAX(\"total\") FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept", $query->getSQL());

        // MIN(total)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MIN, "total")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], $dataSource);

        $this->assertEquals("SELECT category, dept, MIN(\"total\") FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept", $query->getSQL());


        // AVG(total)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_AVG, "total")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], $dataSource);

        $this->assertEquals("SELECT category, dept, AVG(\"total\") FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept", $query->getSQL());

    }


    public function testSummariseTransformationCorrectlyAppliesSelectAndGroupByClauseForCustomExpressions() {

        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSource->returnValue("getConfig", MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class));
        $dataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, null, "COUNT(*) + SUM([[total]])")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], $dataSource);

        $this->assertEquals("SELECT category, dept, COUNT(*) + SUM(\"total\") FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept", $query->getSQL());


    }


    public function testCustomLabelsAreAppliedToExpressionsIfSupplied() {

        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSource->returnValue("getConfig", MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class));
        $dataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_COUNT, null, null, "Total Records"),
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, null, "COUNT(*) + SUM([[total]])", "Custom Metric")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], $dataSource);

        $this->assertEquals("SELECT category, dept, COUNT(*) \"totalRecords\", COUNT(*) + SUM(\"total\") \"customMetric\" FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept", $query->getSQL());


    }


    public function testExplicitlyConfiguredDatasourceColumnsAreUnsetFollowingAnApplyTransformationForSummarisation() {

        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $config = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $dataSource->returnValue("getConfig", $config);
        $dataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());

        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_COUNT, null, null, "Total Records"),
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, null, "COUNT(*) + SUM(total)", "Custom Metric")]);


        $transformationProcessor = new SummariseTransformationProcessor();
        $transformationProcessor->applyTransformation($summariseTransformation, $dataSource, []);

        $this->assertTrue($config->methodWasCalled("setColumns", [[]]));

    }


    public function testDoubleSummarisationCorrectlyWrapsInnerQuery() {

        $dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $dataSource->returnValue("getConfig", MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class));
        $dataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_COUNT, null, null, "Total Records"),
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, null, "COUNT(*) + SUM([[total]])", "Custom Metric")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], $dataSource);

        $this->assertEquals("SELECT category, dept, COUNT(*) \"totalRecords\", COUNT(*) + SUM(\"total\") \"customMetric\" FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept", $query->getSQL());


        // Second summarisation
        $secondSummariseTransformation = new SummariseTransformation(["category"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_SUM, "customMetric")
        ]);

        $query = $transformationProcessor->updateQuery($secondSummariseTransformation, $query, [], $dataSource);
        $this->assertEquals("SELECT category, SUM(\"customMetric\") FROM (SELECT category, dept, COUNT(*) \"totalRecords\", COUNT(*) + SUM(\"total\") \"customMetric\" FROM (SELECT name,category,dept FROM my_table) S1 GROUP BY category, dept) S2 GROUP BY category", $query->getSQL());


    }


}