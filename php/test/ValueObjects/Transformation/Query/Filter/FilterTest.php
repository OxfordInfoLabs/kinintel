<?php

namespace Kinintel\ValueObjects\Transformation\Filter;

include_once "autoloader.php";

class FilterTest extends \PHPUnit\Framework\TestCase {


    public function testWhenBlankFilterTypeSuppliedItDefaultsAccordingToContentSuppliedForValue() {

        // Equals ones - simple primitive
        $filter = new Filter("name", "Hello World");
        $this->assertEquals(Filter::FILTER_TYPE_EQUALS, $filter->getFilterType());

        $filter = new Filter("name", 33);
        $this->assertEquals(Filter::FILTER_TYPE_EQUALS, $filter->getFilterType());

        $filter = new Filter("name", true);
        $this->assertEquals(Filter::FILTER_TYPE_EQUALS, $filter->getFilterType());


        // Wildcard strings match to like.
        $filter = new Filter("name", "*winging");
        $this->assertEquals(Filter::FILTER_TYPE_LIKE, $filter->getFilterType());

        $filter = new Filter("name", "winging*");
        $this->assertEquals(Filter::FILTER_TYPE_LIKE, $filter->getFilterType());

        $filter = new Filter("name", "wing*ing");
        $this->assertEquals(Filter::FILTER_TYPE_LIKE, $filter->getFilterType());


        // Arrays map to in.
        $filter = new Filter("name", ["Hello World"]);
        $this->assertEquals(Filter::FILTER_TYPE_IN, $filter->getFilterType());

        $filter = new Filter("name", [33, 44]);
        $this->assertEquals(Filter::FILTER_TYPE_IN, $filter->getFilterType());

        $filter = new Filter("name", [true, false]);
        $this->assertEquals(Filter::FILTER_TYPE_IN, $filter->getFilterType());


    }

}