<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Query\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Query\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Query\FilterQuery;

include_once "autoloader.php";

class FilterQueryProcessorTest extends \PHPUnit\Framework\TestCase {


    public function testEqualsFiltersAreAppliedCorrectly() {
        $processor = new FilterQueryProcessor();

        try {
            $processor->updateQuery(new FilterQuery([
                new Filter("name", ["hello"], Filter::FILTER_TYPE_EQUALS)
            ]), new SQLQuery("SELECT * FROM test_data"));
            $this->fail("Should have thrown here");
        } catch (DatasourceTransformationException $e) {
            $this->assertTrue(true);
        }

        $query = $processor->updateQuery(new FilterQuery([
            new Filter("name", "Jeeves")
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE name = ?", [
            "Jeeves"
        ]), $query);


    }


    public function testNotEqualsFiltersAppliedCorrectly() {
        $processor = new FilterQueryProcessor();

        try {
            $processor->updateQuery(new FilterQuery([
                new Filter("name", ["hello"], Filter::FILTER_TYPE_NOT_EQUALS)
            ]), new SQLQuery("SELECT * FROM test_data"));
            $this->fail("Should have thrown here");
        } catch (DatasourceTransformationException $e) {
            $this->assertTrue(true);
        }

        $query = $processor->updateQuery(new FilterQuery([
            new Filter("name", "Jeeves", Filter::FILTER_TYPE_NOT_EQUALS)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE name <> ?", [
            "Jeeves"
        ]), $query);
    }


    public function testNullFiltersAppliedCorrectly() {
        $processor = new FilterQueryProcessor();
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("name", "", Filter::FILTER_TYPE_NULL)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE name IS NULL", [
        ]), $query);
    }

    public function testNotNullFiltersAppliedCorrectly() {
        $processor = new FilterQueryProcessor();
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("name", "", Filter::FILTER_TYPE_NOT_NULL)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE name IS NOT NULL", [
        ]), $query);
    }

    public function testGreaterThanFiltersAppliedCorrectly() {
        $processor = new FilterQueryProcessor();
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", 25, Filter::FILTER_TYPE_GREATER_THAN)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age > ?", [
            25
        ]), $query);
    }

    public function testGreaterThanEqualFiltersAppliedCorrectly() {
        $processor = new FilterQueryProcessor();
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", 25, Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age >= ?", [
            25
        ]), $query);
    }

    public function testLessThanFiltersAppliedCorrectly() {
        $processor = new FilterQueryProcessor();
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", 25, Filter::FILTER_TYPE_LESS_THAN)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age < ?", [
            25
        ]), $query);
    }

    public function testLessThanEqualFiltersAppliedCorrectly() {
        $processor = new FilterQueryProcessor();
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", 25, Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age <= ?", [
            25
        ]), $query);
    }

    public function testLikeFiltersAppliedCorrectly() {

        $processor = new FilterQueryProcessor();

        // Straight like (effectively equals)
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("name", "mark", Filter::FILTER_TYPE_LIKE)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE name LIKE ?", [
            "mark"
        ]), $query);


        // Wilcarded like using *
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("name", "m*", Filter::FILTER_TYPE_LIKE)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE name LIKE ?", [
            "m%"
        ]), $query);

        $query = $processor->updateQuery(new FilterQuery([
            new Filter("name", "*m*", Filter::FILTER_TYPE_LIKE)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE name LIKE ?", [
            "%m%"
        ]), $query);

    }


    public function testBetweenFilterAppliedCorrectly() {

        $processor = new FilterQueryProcessor();


        try {
            $processor->updateQuery(new FilterQuery([
                new Filter("age", 18, Filter::FILTER_TYPE_BETWEEN)
            ]), new SQLQuery("SELECT * FROM test_data"));
            $this->fail("Should have thrown here");
        } catch (DatasourceTransformationException $e) {

        }

        try {
            $processor->updateQuery(new FilterQuery([
                new Filter("age", [18], Filter::FILTER_TYPE_BETWEEN)
            ]), new SQLQuery("SELECT * FROM test_data"));
            $this->fail("Should have thrown here");
        } catch (DatasourceTransformationException $e) {

        }


        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", [18, 65], Filter::FILTER_TYPE_BETWEEN)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age BETWEEN ? AND ?", [
            18, 65
        ]), $query);

    }


    public function testInFilterAppliedCorrectly() {
        $processor = new FilterQueryProcessor();

        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_IN)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age IN (?,?,?,?,?,?,?,?,?,?)", [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        ]), $query);

    }


    public function testNotInFilterAppliedCorrectly() {
        $processor = new FilterQueryProcessor();

        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_NOT_IN)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age NOT IN (?,?,?,?,?,?,?,?,?,?)", [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        ]), $query);

    }


    public function testMultipleFiltersAppliedWithLogicCorrectly() {

        $processor = new FilterQueryProcessor();

        // And one
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_NOT_IN),
            new Filter("weight", 10, Filter::FILTER_TYPE_GREATER_THAN)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age NOT IN (?,?,?,?,?,?,?,?,?,?) AND weight > ?", [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 10
        ]), $query);


        // Or one
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_NOT_IN),
            new Filter("weight", 10, Filter::FILTER_TYPE_GREATER_THAN)
        ], [], FilterJunction::LOGIC_OR), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age NOT IN (?,?,?,?,?,?,?,?,?,?) OR weight > ?", [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 10
        ]), $query);


    }


    public function testNestedFilterJunctionsAreAppliedCorrectlyAsExpected() {

        $processor = new FilterQueryProcessor();

        // And one
        $query = $processor->updateQuery(new FilterQuery([
            new Filter("age", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_NOT_IN),
            new Filter("weight", 10, Filter::FILTER_TYPE_GREATER_THAN)
        ], [
            new FilterJunction([
                new Filter("name", "bob%", Filter::FILTER_TYPE_LIKE),
                new Filter("name", "mary%", Filter::FILTER_TYPE_LIKE),
            ], [], FilterJunction::LOGIC_OR)
        ]), new SQLQuery("SELECT * FROM test_data"));

        $this->assertEquals(new SQLQuery("SELECT * FROM test_data WHERE age NOT IN (?,?,?,?,?,?,?,?,?,?) AND weight > ? AND (name LIKE ? OR name LIKE ?)", [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 10, "bob%", "mary%"
        ]), $query);

    }


}

