<?php

namespace Kinintel\Test\Services\Util;

use Kinintel\Exception\AmbiguousQueryLogicException;
use Kinintel\Exception\InvalidQueryClauseException;
use Kinintel\Services\Util\FilterQueryParser;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterLogic;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;

include_once "autoloader.php";

class FilterQueryParserTest extends TestBase {


    /**
     * @var FilterQueryParser
     */
    private FilterQueryParser $filterQueryParser;


    public function setUp(): void {
        $this->filterQueryParser = new FilterQueryParser();
    }


    /**
     * @doesNotPerformAssertions
     */
    public function testInvalidQueryClauseExceptionRaisedIfInvalidOperatorsSupplied() {
        $query = "id badoperator";

        try {
            $this->filterQueryParser->convertQueryToFilterJunction($query);
            $this->fail("Bad query");
        } catch (InvalidQueryClauseException $e) {
        }


        $query = "id badoperator 3";

        try {
            $this->filterQueryParser->convertQueryToFilterJunction($query);
            $this->fail("Bad query");
        } catch (InvalidQueryClauseException $e) {
        }
    }


    public function testCanConvertValidSingleEqualsQueryStringIntoJunction() {

        // Simple numerical value
        $query = "id == 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, FilterType::eq)
        ]), $junction);

        // String values quoted
        $query = "name == 'Mark'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", "Mark", FilterType::eq)
        ]), $junction);

        // String values with escaped quotes
        $query = "name == 'Mark\'s Domain'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", "Mark's Domain", FilterType::eq)
        ]), $junction);

        // String values in double quotes
        $query = 'name == "Mark"';
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", "Mark", FilterType::eq)
        ]), $junction);

    }


    public function testCanConvertValidOtherSimpleSingleValuedOperatorTypesQueryStringIntoJunction() {

        // Greater than
        $query = "id > 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, FilterType::gt)
        ]), $junction);

        // Greater than or equal to
        $query = "id >= 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, FilterType::gte)
        ]), $junction);

        // Less than
        $query = "id < 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, FilterType::lt)
        ]), $junction);

        // Less than or equal to
        $query = "id <= 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, FilterType::lte)
        ]), $junction);

        // Not equals
        $query = "id != 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, FilterType::neq)
        ]), $junction);

        // Null
        $query = "id isnull";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", null, FilterType::null)
        ]), $junction);

        // Not null
        $query = "id isnotnull";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", null, FilterType::notnull)
        ]), $junction);

        // Contains
        $query = "id contains 5";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 5, FilterType::contains)
        ]), $junction);

        // Starts with
        $query = "id startswith 5";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 5, FilterType::startswith)
        ]), $junction);

        // Starts with
        $query = "id endswith 5";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 5, FilterType::endswith)
        ]), $junction);


        // Like
        $query = "name like '*mark*'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", ["*mark*", Filter::LIKE_MATCH_WILDCARD], FilterType::like)
        ]), $junction);

        // Not Like
        $query = "name notlike '*mark*'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", ["*mark*", Filter::LIKE_MATCH_WILDCARD], FilterType::notlike)
        ]), $junction);

        // Like Regexp
        $query = "name likeregexp '*mark*'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", ["*mark*", Filter::LIKE_MATCH_REGEXP], FilterType::like)
        ]), $junction);

        // Not Like
        $query = "name notlikeregexp '*mark*'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", ["*mark*", Filter::LIKE_MATCH_REGEXP], FilterType::notlike)
        ]), $junction);

    }

    public function testCanConvertValidMultiValuedOperatorTypeQueryStringIntoFilterJunction() {

        // in with string values
        $query = "type in ['bob', 'mary', 'paul']";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[type]]", ["bob", "mary", "paul"], FilterType::in)
        ]), $junction);

        // in with numeric values
        $query = "age in [12,13,14]";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[age]]", [12, 13, 14], FilterType::in)
        ]), $junction);

        // not in with string values
        $query = "type notin ['bob', 'mary', 'paul']";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[type]]", ["bob", "mary", "paul"], FilterType::notin)
        ]), $junction);

        // not in with numeric values
        $query = "age notin [12,13,14]";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[age]]", [12, 13, 14], FilterType::notin)
        ]), $junction);


    }


    public function testCanConvertValidSimpleLogicalJunctions() {

        // AND junction
        $query = "id isnull && name == 'bob'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", null, FilterType::null),
            new Filter("[[name]]", "bob", FilterType::eq)
        ]), $junction);

        // OR junction
        $query = "id isnull || name == 'bob'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", null, FilterType::null),
            new Filter("[[name]]", "bob", FilterType::eq)
        ], [], FilterLogic::OR), $junction);

        // Multiple clauses
        $query = "id isnull && name == 'bob' && age > 5";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", null, FilterType::null),
            new Filter("[[name]]", "bob", FilterType::eq),
            new Filter("[[age]]", 5, FilterType::gt),
        ]), $junction);

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testInvalidQueryClauseExceptionIfInvalidJunctionsSupplied() {

        // Bad junction
        $query = "id isnull FIND name == 'bob'";

        try {
            $this->filterQueryParser->convertQueryToFilterJunction($query);
            $this->fail("Bad query");
        } catch (InvalidQueryClauseException $e) {
        }

    }


    /**
     * @doesNotPerformAssertions
     */
    public function testAmbiguousQueryLogicExceptionRaisedIfMixedLogicSuppliedToJunction() {


        // Bad junction
        $query = "id isnull && name == 'bob' || age < 25";

        try {
            $this->filterQueryParser->convertQueryToFilterJunction($query);
            $this->fail("Ambiguous logic");
        } catch (AmbiguousQueryLogicException $e) {
        }


    }


    public function testSimpleBracketedExpressionsAreEvaluatedToFilterGroups() {

        $query = "(id isnull && name == 'bob') || (name contains 'mary' && age < 32)";

        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([], [
            new FilterJunction([
                new Filter("[[id]]", null, FilterType::null),
                new Filter("[[name]]", "bob", FilterType::eq)
            ]),
            new FilterJunction([
                new Filter("[[name]]", "mary", FilterType::contains),
                new Filter("[[age]]", 32, FilterType::lt)
            ])
        ], FilterLogic::OR), $junction);


        $query = "age > 18 && (id isnull && name == 'bob') && (name contains 'mary' && age < 32)";

        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[age]]", 18, FilterType::gt)
        ], [
            new FilterJunction([
                new Filter("[[id]]", null, FilterType::null),
                new Filter("[[name]]", "bob", FilterType::eq)
            ]),
            new FilterJunction([
                new Filter("[[name]]", "mary", FilterType::contains),
                new Filter("[[age]]", 32, FilterType::lt)
            ])
        ], FilterLogic::AND), $junction);


    }


    public function testNestedBracketedExpressionsAreEvaluatedCorrectly() {

        $query = "age > 18 && ((id isnull && name == 'bob') || ((name contains 'mary' || name contains 'bob') && age < 32))";

        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[age]]", 18, FilterType::gt)
        ], [
            new FilterJunction([], [
                new FilterJunction([
                        new Filter("[[id]]", null, FilterType::null),
                        new Filter("[[name]]", "bob", FilterType::eq)
                    ]
                ),
                new FilterJunction([
                    new Filter("[[age]]", 32, FilterType::lt)
                ], [
                    new FilterJunction([
                        new Filter("[[name]]", "mary", FilterType::contains),
                        new Filter("[[name]]", "bob", FilterType::contains)
                    ], [], FilterLogic::OR)
                ])
            ], FilterLogic::OR)
        ], FilterLogic::AND), $junction);

    }


}