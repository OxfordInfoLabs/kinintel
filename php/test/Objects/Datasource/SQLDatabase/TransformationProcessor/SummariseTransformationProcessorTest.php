<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SummariseTransformationProcessor;
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

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], null);

        $this->assertEquals("SELECT category, dept, COUNT(*) FROM my_table GROUP BY category, dept", $query->getSQL());

        // SUM(total)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_SUM, "total")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], null);

        $this->assertEquals("SELECT category, dept, SUM(total) FROM my_table GROUP BY category, dept", $query->getSQL());


        // MAX(total)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MAX, "total")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], null);

        $this->assertEquals("SELECT category, dept, MAX(total) FROM my_table GROUP BY category, dept", $query->getSQL());

        // MIN(total)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MIN, "total")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], null);

        $this->assertEquals("SELECT category, dept, MIN(total) FROM my_table GROUP BY category, dept", $query->getSQL());


        // AVG(total)
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_AVG, "total")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], null);

        $this->assertEquals("SELECT category, dept, AVG(total) FROM my_table GROUP BY category, dept", $query->getSQL());

    }


    public function testSummariseTransformationCorrectlyAppliesSelectAndGroupByClauseForCustomExpressions() {

        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, null, "COUNT(*) + SUM(total)")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], null);

        $this->assertEquals("SELECT category, dept, COUNT(*) + SUM(total) FROM my_table GROUP BY category, dept", $query->getSQL());


    }


    public function testCustomLabelsAreAppliedToExpressionsIfSupplied() {
        $summariseTransformation = new SummariseTransformation(["category", "dept"], [
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_COUNT, null, null, "Total Records"),
            new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, null, "COUNT(*) + SUM(total)", "Custom Metric")]);

        $query = new SQLQuery("name,category,dept", "my_table");

        $transformationProcessor = new SummariseTransformationProcessor();
        $query = $transformationProcessor->updateQuery($summariseTransformation, $query, [], null);

        $this->assertEquals("SELECT category, dept, COUNT(*) totalRecords, COUNT(*) + SUM(total) customMetric FROM my_table GROUP BY category, dept", $query->getSQL());


    }


}