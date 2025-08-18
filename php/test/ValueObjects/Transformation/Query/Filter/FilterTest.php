<?php

namespace Kinintel\ValueObjects\Transformation\Filter;

include_once "autoloader.php";

class FilterTest extends \PHPUnit\Framework\TestCase {


    public function testWhenBlankFilterTypeSuppliedItDefaultsAccordingToContentSuppliedForValue() {

        // Equals ones - simple primitive
        $filter = new Filter("name", "Hello World");
        $this->assertEquals(FilterType::eq, $filter->getFilterType());

        $filter = new Filter("name", 33);
        $this->assertEquals(FilterType::eq, $filter->getFilterType());

        $filter = new Filter("name", true);
        $this->assertEquals(FilterType::eq, $filter->getFilterType());


        // Wildcard strings match to like and convert value to array.
        $filter = new Filter("name", "*winging");
        $this->assertEquals(FilterType::like, $filter->getFilterType());
        $this->assertEquals(["*winging", Filter::LIKE_MATCH_WILDCARD], $filter->getRhsExpression());

        $filter = new Filter("name", "winging*");
        $this->assertEquals(FilterType::like, $filter->getFilterType());
        $this->assertEquals(["winging*", Filter::LIKE_MATCH_WILDCARD], $filter->getRhsExpression());

        $filter = new Filter("name", "wing*ing");
        $this->assertEquals(FilterType::like, $filter->getFilterType());
        $this->assertEquals(["wing*ing", Filter::LIKE_MATCH_WILDCARD], $filter->getRhsExpression());

        // Regexp strings match to like and convert value to array
        $filter = new Filter("name", "/winging/");
        $this->assertEquals(FilterType::like, $filter->getFilterType());
        $this->assertEquals(["winging", Filter::LIKE_MATCH_REGEXP], $filter->getRhsExpression());

        $filter = new Filter("name", "/^winging.*$/");
        $this->assertEquals(FilterType::like, $filter->getFilterType());
        $this->assertEquals(["^winging.*$", Filter::LIKE_MATCH_REGEXP], $filter->getRhsExpression());


        // Arrays map to in.
        $filter = new Filter("name", ["Hello World"]);
        $this->assertEquals(FilterType::in, $filter->getFilterType());

        $filter = new Filter("name", [33, 44]);
        $this->assertEquals(FilterType::in, $filter->getFilterType());

        $filter = new Filter("name", [true, false]);
        $this->assertEquals(FilterType::in, $filter->getFilterType());

    }


    public function testRHSValuesAreRemovedForNullAndNotNullFilterTypesIfSupplied() {

        $filter = new Filter("name", 1, FilterType::null);
        $this->assertNull($filter->getRhsExpression());

        $filter = new Filter("name", 1, FilterType::isnull);
        $this->assertNull($filter->getRhsExpression());

        $filter = new Filter("name", 1, FilterType::notnull);
        $this->assertNull($filter->getRhsExpression());

        $filter = new Filter("name", 1, FilterType::isnotnull);
        $this->assertNull($filter->getRhsExpression());

    }


    public function testForInAndNotInFiltersIfStringSuppliedTheseAreConvertedToArrays() {

        $filter = new Filter("name", "mark,nathan,pete", FilterType::in);
        $this->assertEquals(["mark", "nathan", "pete"], $filter->getRhsExpression());

        $filter = new Filter("name", "mark,nathan,pete", FilterType::notin);
        $this->assertEquals(["mark", "nathan", "pete"], $filter->getRhsExpression());

        // Ensure we can enclose values with commas too !
        $filter = new Filter("name", "\"mark,nathan\",pete", FilterType::notin);
        $this->assertEquals(["mark,nathan", "pete"], $filter->getRhsExpression());



    }


    public function testCanCreateArrayOfFieldBasedFiltersWhereArrayKeyIsComposedOfLHSFieldAndFilterType() {

        $array = [
            "test" => "Hello",
            "param1_eq" => "Test",
            "param2_gt" => 5,
            "longer_param_contains" => "Badger",
            "even_longer_param_like" => "/^mark.*$/",
            "param3_like" => "*hello*",
            "param4_in" => "mark,nathan,pete",
            "param5_notin" => "james",
            "param6_isnull" => 1
        ];

        $filters = Filter::createFiltersFromFieldTypeIndexedArray($array);

        $this->assertEquals([
            new Filter("[[param1]]", "Test", FilterType::eq),
            new Filter("[[param2]]", 5, FilterType::gt),
            new Filter("[[longer_param]]", "Badger", FilterType::contains),
            new Filter("[[even_longer_param]]", ["^mark.*$", Filter::LIKE_MATCH_REGEXP], FilterType::like),
            new Filter("[[param3]]", ["*hello*", Filter::LIKE_MATCH_WILDCARD], FilterType::like),
            new Filter("[[param4]]", ["mark", "nathan", "pete"], FilterType::in),
            new Filter("[[param5]]", ["james"], FilterType::notin),
            new Filter("[[param6]]", null, FilterType::isnull)
        ], $filters);


    }

}