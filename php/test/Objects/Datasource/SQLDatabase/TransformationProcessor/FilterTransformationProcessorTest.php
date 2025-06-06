<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\TemplateParser;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Formula\Expression;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;
use Kinintel\ValueObjects\Transformation\InclusionCriteriaType;

include_once "autoloader.php";

class FilterTransformationProcessorTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var TemplateParser
     */
    private $templateParser;

    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;


    /**
     * @var MockObject
     */
    private $dataSource;


    public function setUp(): void {
        $this->templateParser = Container::instance()->get(TemplateParser::class);
        $this->databaseConnection = new SQLite3DatabaseConnection();
        $this->dataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $this->dataSource->returnValue("returnDatabaseConnection", $this->databaseConnection);
    }


    public function testEqualsFiltersAreAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[name]]", "Jeeves")
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"name\" = ?", $query->getSQL());
        $this->assertEquals(["Jeeves"], $query->getParameters());

    }


    public function testNotEqualsFiltersAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);


        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[name]]", "Jeeves", Filter::FILTER_TYPE_NOT_EQUALS)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"name\" <> ?", $query->getSQL());
        $this->assertEquals([
            "Jeeves"
        ], $query->getParameters());
    }


    public function testNullFiltersAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[name]]", "", Filter::FILTER_TYPE_NULL)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"name\" IS NULL", $query->getSQL());
    }

    public function testNotNullFiltersAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[name]]", "", Filter::FILTER_TYPE_NOT_NULL)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"name\" IS NOT NULL", $query->getSQL());
    }

    public function testGreaterThanFiltersAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", 25, Filter::FILTER_TYPE_GREATER_THAN)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" > ?", $query->getSQL());
        $this->assertEquals([
            25
        ], $query->getParameters());
    }

    public function testGreaterThanEqualFiltersAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", 25, Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" >= ?", $query->getSQL());
        $this->assertEquals([
            25
        ], $query->getParameters());
    }

    public function testLessThanFiltersAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", 25, Filter::FILTER_TYPE_LESS_THAN)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" < ?", $query->getSQL());
        $this->assertEquals([
            25
        ], $query->getParameters());
    }

    public function testLessThanEqualFiltersAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", 25, Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" <= ?", $query->getSQL());
        $this->assertEquals([
            25
        ], $query->getParameters());
    }

    public function testLikeFiltersAppliedCorrectly() {

        $processor = new FilterTransformationProcessor($this->templateParser);

        // Straight like (effectively equals)
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[name]]", "mark", Filter::FILTER_TYPE_LIKE)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"name\" LIKE ?", $query->getSQL());
        $this->assertEquals([
            "mark"
        ], $query->getParameters());


        // Wilcarded like using *
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[name]]", "m*", Filter::FILTER_TYPE_LIKE)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"name\" LIKE ?", $query->getSQL());
        $this->assertEquals([
            "m%"
        ], $query->getParameters());

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[name]]", "*m*", Filter::FILTER_TYPE_LIKE)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"name\" LIKE ?", $query->getSQL());
        $this->assertEquals([
            "%m%"
        ], $query->getParameters());

    }


    public function testBetweenFilterAppliedCorrectly() {

        $processor = new FilterTransformationProcessor($this->templateParser);


        try {
            $processor->updateQuery(new FilterTransformation([
                new Filter("[[age]]", 18, Filter::FILTER_TYPE_BETWEEN)
            ]), new SQLQuery("*", "test_data"), [], $this->dataSource);
            $this->fail("Should have thrown here");
        } catch (DatasourceTransformationException $e) {

        }

        try {
            $processor->updateQuery(new FilterTransformation([
                new Filter("[[age]]", [18], Filter::FILTER_TYPE_BETWEEN)
            ]), new SQLQuery("*", "test_data"), [], $this->dataSource);
            $this->fail("Should have thrown here");
        } catch (DatasourceTransformationException $e) {

        }


        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", [18, 65], Filter::FILTER_TYPE_BETWEEN)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" BETWEEN ? AND ?", $query->getSQL());
        $this->assertEquals([
            18, 65
        ], $query->getParameters());

    }


    public function testInFilterAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_IN)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" IN (?,?,?,?,?,?,?,?,?,?)", $query->getSQL());
        $this->assertEquals([
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        ], $query->getParameters());

    }


    public function testNotInFilterAppliedCorrectly() {
        $processor = new FilterTransformationProcessor($this->templateParser);

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_NOT_IN)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" NOT IN (?,?,?,?,?,?,?,?,?,?)", $query->getSQL());
        $this->assertEquals([
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        ], $query->getParameters());

    }

    public function testBitwiseAndFilterAppliedCorrectly() {

        $processor = new FilterTransformationProcessor($this->templateParser);
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", 25, Filter::FILTER_TYPE_BITWISE_AND)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" & ?", $query->getSQL());
        $this->assertEquals([
            25
        ], $query->getParameters());

    }

    public function testBitwiseOrFilterAppliedCorrectly() {

        $processor = new FilterTransformationProcessor($this->templateParser);
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", 25, Filter::FILTER_TYPE_BITWISE_OR)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" | ?", $query->getSQL());
        $this->assertEquals([
            25
        ], $query->getParameters());

    }

    public function testMultipleFiltersAppliedWithLogicCorrectly() {

        $processor = new FilterTransformationProcessor($this->templateParser);

        // And one
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_NOT_IN),
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" NOT IN (?,?,?,?,?,?,?,?,?,?) AND \"weight\" > ?", $query->getSQL());
        $this->assertEquals([
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 10
        ], $query->getParameters());


        // Or one
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_NOT_IN),
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN)
        ], [], FilterJunction::LOGIC_OR), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" NOT IN (?,?,?,?,?,?,?,?,?,?) OR \"weight\" > ?", $query->getSQL());
        $this->assertEquals([
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 10
        ], $query->getParameters());


    }


    public function testFiltersWithInclusionCriteriaAppliedCorrectly() {


        $processor = new FilterTransformationProcessor($this->templateParser);

        // Test one without parameters set, confirm no where clause written
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN, InclusionCriteriaType::ParameterPresent, "test")
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data", $query->getSQL());
        $this->assertEquals([], $query->getParameters());


        // Now one with a parameter set
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN, InclusionCriteriaType::ParameterPresent, "test")
        ]), new SQLQuery("*", "test_data"), ["test" => 1], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"weight\" > ?", $query->getSQL());
        $this->assertEquals([10], $query->getParameters());

        // Multi parameter type
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[weight]]", ["{{test}}"], Filter::FILTER_TYPE_IN, InclusionCriteriaType::ParameterPresent, "test"),
            new Filter("[[age]]", ["{{test2}}"], Filter::FILTER_TYPE_IN, InclusionCriteriaType::ParameterPresent, "test2" )
        ]), new SQLQuery("*", "test_data"), ["test" => [], "test2" => []], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data", $query->getSQL());
        $this->assertEquals([], $query->getParameters());



        // A conditional filter junction without parameters set
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN)
        ], [], FilterJunction::LOGIC_AND, InclusionCriteriaType::ParameterPresent, "test"),
            new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data", $query->getSQL());
        $this->assertEquals([], $query->getParameters());


        // And with a parameter set
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN)
        ], [], FilterJunction::LOGIC_AND, InclusionCriteriaType::ParameterPresent, "test"),
            new SQLQuery("*", "test_data"), ["test" => 1], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"weight\" > ?", $query->getSQL());
        $this->assertEquals([10], $query->getParameters());


        // And with a no parameter present rule
        // Test one without parameters set, confirm no where clause written
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN, InclusionCriteriaType::ParameterNotPresent, "test")
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"weight\" > ?", $query->getSQL());

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN, InclusionCriteriaType::ParameterNotPresent, "test")
        ]), new SQLQuery("*", "test_data"), ["test" => 1], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data", $query->getSQL());


    }


    public function testNestedFilterJunctionsAreAppliedCorrectlyAsExpected() {

        $processor = new FilterTransformationProcessor($this->templateParser);

        // And one
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_NOT_IN),
            new Filter("[[weight]]", 10, Filter::FILTER_TYPE_GREATER_THAN)
        ], [
            new FilterJunction([
                new Filter("[[name]]", "bob%", Filter::FILTER_TYPE_LIKE),
                new Filter("[[name]]", "mary%", Filter::FILTER_TYPE_LIKE),
            ], [], FilterJunction::LOGIC_OR)
        ]), new SQLQuery("*", "test_data"), [], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" NOT IN (?,?,?,?,?,?,?,?,?,?) AND \"weight\" > ? AND (\"name\" LIKE ? OR \"name\" LIKE ?)", $query->getSQL());
        $this->assertEquals([
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 10, "bob%", "mary%"
        ], $query->getParameters());

    }


    public function testIfGroupByClausePresentInPassedQueryHavingClauseWrittenInstead() {

        $processor = new FilterTransformationProcessor($this->templateParser);

        $sourceQuery = new SQLQuery("*", "test_data");
        $sourceQuery->setWhereClause("category = 'tech'");
        $sourceQuery->setGroupByClause("age, count(*)", "age");

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], Filter::FILTER_TYPE_IN)
        ]), $sourceQuery, [], $this->dataSource);

        $this->assertEquals("SELECT age, count(*) FROM test_data WHERE category = 'tech' GROUP BY age HAVING \"age\" IN (?,?,?,?,?,?,?,?,?,?)", $query->getSQL());
        $this->assertEquals([
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        ], $query->getParameters());

    }


    public function testIfParameterValuesPassedThroughTheseAreSubstitutedInLHSAsPlaceholders() {

        $processor = new FilterTransformationProcessor($this->templateParser);

        $sourceQuery = new SQLQuery("*", "test_data");

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("{{jobTitle}}", "Bobby Brown")
        ]), $sourceQuery, [
            "jobTitle" => "Company Director"
        ], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE ? = ?", $query->getSQL());
        $this->assertEquals([
            "Company Director",
            "Bobby Brown"
        ], $query->getParameters());

    }

    public function testIfParameterValuesPassedThroughTheseAreSubstitutedWhereDoubleBracesSuppliedInRHS() {
        $processor = new FilterTransformationProcessor($this->templateParser);

        $sourceQuery = new SQLQuery("*", "test_data");

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[job]]", "{{jobTitle}}")
        ]), $sourceQuery, [
            "jobTitle" => "Company Director"
        ], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"job\" = ?", $query->getSQL());
        $this->assertEquals([
            "Company Director"
        ], $query->getParameters());


        $sourceQuery = new SQLQuery("*", "test_data");

        // Check an array one
        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[age]]", "{{ages}}", Filter::FILTER_TYPE_IN)
        ]), $sourceQuery, [
            "ages" => [10, 11, 12, 13, 14, 15]
        ], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"age\" IN (?,?,?,?,?,?)", $query->getSQL());
        $this->assertEquals([
            10,
            11,
            12,
            13,
            14,
            15
        ], $query->getParameters());


        $sourceQuery = new SQLQuery("*", "test_data");
        $sourceQuery->setWhereClause("category = 'tech'");
        $sourceQuery->setGroupByClause("age, count(*)", "age");

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[job]]", "{{jobTitle}}")
        ]), $sourceQuery, [
            "jobTitle" => "Company Director"
        ], $this->dataSource);

        $this->assertEquals("SELECT age, count(*) FROM test_data WHERE category = 'tech' GROUP BY age HAVING \"job\" = ?", $query->getSQL());
        $this->assertEquals([
            "Company Director"
        ], $query->getParameters());
    }


    public function testLikeExpressionsInvolvingParametersAreSupportedCorrectly() {

        $processor = new FilterTransformationProcessor($this->templateParser);

        $sourceQuery = new SQLQuery("*", "test_data");

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[job]]", "*{{jobTitle}}*", Filter::FILTER_TYPE_LIKE)
        ]), $sourceQuery, [
            "jobTitle" => "Company Director"
        ], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE \"job\" LIKE ?", $query->getSQL());
        $this->assertEquals([
            "%Company Director%"
        ], $query->getParameters());


    }

    public function testSecondFilterAppliedIsAddedToQueryUsingANDStructure() {

        $processor = new FilterTransformationProcessor($this->templateParser);

        $sourceQuery = new SQLQuery("*", "test_data");

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[job]]", "*{{jobTitle}}*", Filter::FILTER_TYPE_LIKE)
        ]), $sourceQuery, [
            "jobTitle" => "Company Director"
        ], $this->dataSource);

        $query = $processor->updateQuery(new FilterTransformation([
            new Filter("[[job]]", "Geek", Filter::FILTER_TYPE_NOT_EQUALS)
        ]), $query, [
            "jobTitle" => "Company Director"
        ], $this->dataSource);

        $this->assertEquals("SELECT * FROM test_data WHERE (\"job\" LIKE ?) AND (\"job\" <> ?)", $query->getSQL());
        $this->assertEquals([
            "%Company Director%",
            "Geek"
        ], $query->getParameters());

    }


    public function testIfFilterAppliedAfterFormulaTransformationQueryIsWrappedFirstToPreventAliasIssues() {

        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $authenticationCredentials->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());

        $dataSource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test", null, [
            new Field("column1"), new Field("column2")
        ]),
            $authenticationCredentials, null);

        $transformation = new FormulaTransformation([
            new Expression("Computed", "[[column1]] + [[column2]]"),
            new Expression("Derived Column", "[[column3]] + 5 / [[column2]]"),
            new Expression("Parameter", "{{test}} + [[column1]]")]);

        $dataSource->applyTransformation($transformation);


        $secondTransformation = new FormulaTransformation([
            new Expression("Second Level", "[[computed]] + [[parameter]]")
        ]);

        $dataSource->applyTransformation($secondTransformation);


        $filterTransformation = new FilterTransformation([new Filter("[[computed]]", 44),
            new Filter("[[secondLevel]]", 26)]);

        $dataSource->applyTransformation($filterTransformation);

        $formulaTransformationProcessor = new FormulaTransformationProcessor();
        $filterTransformationProcessor = new FilterTransformationProcessor($this->templateParser);

        $query = new SQLQuery("*", "sample_table");

        $query = $formulaTransformationProcessor->updateQuery($transformation, $query, ["test" => "Hello"], $dataSource);
        $query = $formulaTransformationProcessor->updateQuery($secondTransformation, $query, ["test" => "Hello"], $dataSource);


        $query = $filterTransformationProcessor->updateQuery($filterTransformation, $query, ["test" => "Hello"], $dataSource);

        $this->assertEquals('SELECT * FROM (SELECT *, "column1" + "column2" computed, "column3" + ? / "column2" derivedColumn, ? + "column1" parameter, ("column1" + "column2") + (? + "column1") secondLevel FROM sample_table) E1 WHERE "computed" = ? AND "secondLevel" = ?', $query->getSQL());


    }


}

