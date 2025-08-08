<?php

namespace Kinintel\Test\Services\Util;

use Kinintel\Services\Util\FilterQueryParser;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;

include_once "autoloader.php";

class FilterQueryParserTest extends TestBase {


    /**
     * @var FilterQueryParser
     */
    private FilterQueryParser $filterQueryParser;


    public function setUp(): void {
        $this->filterQueryParser = new FilterQueryParser();
    }

    public function testCanConvertValidSingleEqualsQueryStringIntoJunction() {

        // Simple numerical value
        $query = "id == 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, Filter::FILTER_TYPE_EQUALS)
        ]), $junction);

        // String values quoted
        $query = "name == 'Mark'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", "Mark", Filter::FILTER_TYPE_EQUALS)
        ]), $junction);

        // String values with escaped quotes
        $query = "name == 'Mark\'s Domain'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", "Mark's Domain", Filter::FILTER_TYPE_EQUALS)
        ]), $junction);

        // String values in double quotes
        $query = 'name == "Mark"';
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", "Mark", Filter::FILTER_TYPE_EQUALS)
        ]), $junction);

    }


    public function testCanConvertValidOtherSimpleSingleValuedOperatorTypesQueryStringIntoJunction() {

        // Greater than
        $query = "id > 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, Filter::FILTER_TYPE_GREATER_THAN)
        ]), $junction);

        // Greater than or equal to
        $query = "id >= 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO)
        ]), $junction);

        // Less than
        $query = "id < 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, Filter::FILTER_TYPE_LESS_THAN)
        ]), $junction);

        // Less than or equal to
        $query = "id <= 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO)
        ]), $junction);

        // Not equals
        $query = "id != 25";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 25, Filter::FILTER_TYPE_NOT_EQUALS)
        ]), $junction);

        // Null
        $query = "id isnull";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", null, Filter::FILTER_TYPE_NULL)
        ]), $junction);

        // Not null
        $query = "id isnotnull";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", null, Filter::FILTER_TYPE_NOT_NULL)
        ]), $junction);

        // Contains
        $query = "id contains 5";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 5, Filter::FILTER_TYPE_CONTAINS)
        ]), $junction);

        // Starts with
        $query = "id startswith 5";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 5, Filter::FILTER_TYPE_STARTS_WITH)
        ]), $junction);

        // Starts with
        $query = "id endswith 5";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[id]]", 5, Filter::FILTER_TYPE_ENDS_WITH)
        ]), $junction);


        // Like
        $query = "name like '*mark*'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", ["*mark*", Filter::LIKE_MATCH_WILDCARD], Filter::FILTER_TYPE_LIKE)
        ]), $junction);

        // Not Like
        $query = "name notlike '*mark*'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", ["*mark*", Filter::LIKE_MATCH_WILDCARD], Filter::FILTER_TYPE_NOT_LIKE)
        ]), $junction);

        // Like Regexp
        $query = "name likeregexp '*mark*'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", ["*mark*", Filter::LIKE_MATCH_REGEXP], Filter::FILTER_TYPE_LIKE)
        ]), $junction);

        // Not Like
        $query = "name notlikeregexp '*mark*'";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[name]]", ["*mark*", Filter::LIKE_MATCH_REGEXP], Filter::FILTER_TYPE_NOT_LIKE)
        ]), $junction);

    }

    public function testCanConvertValidMultiValuedOperatorTypeQueryStringIntoFilterJunction(){

        // in with string values
        $query = "type in ['bob', 'mary', 'paul']";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[type]]", ["bob","mary","paul"], Filter::FILTER_TYPE_IN)
        ]), $junction);

        // in with numeric values
        $query = "age in [12,13,14]";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[age]]", [12,13,14], Filter::FILTER_TYPE_IN)
        ]), $junction);

        // not in with string values
        $query = "type notin ['bob', 'mary', 'paul']";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[type]]", ["bob","mary","paul"], Filter::FILTER_TYPE_NOT_IN)
        ]), $junction);

        // not in with numeric values
        $query = "age notin [12,13,14]";
        $junction = $this->filterQueryParser->convertQueryToFilterJunction($query);
        $this->assertEquals(new FilterJunction([
            new Filter("[[age]]", [12,13,14], Filter::FILTER_TYPE_NOT_IN)
        ]), $junction);

        
    }


}