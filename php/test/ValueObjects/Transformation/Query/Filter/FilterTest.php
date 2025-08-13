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


        // Wildcard strings match to like.
        $filter = new Filter("name", "*winging");
        $this->assertEquals(FilterType::like, $filter->getFilterType());

        $filter = new Filter("name", "winging*");
        $this->assertEquals(FilterType::like, $filter->getFilterType());

        $filter = new Filter("name", "wing*ing");
        $this->assertEquals(FilterType::like, $filter->getFilterType());


        // Arrays map to in.
        $filter = new Filter("name", ["Hello World"]);
        $this->assertEquals(FilterType::in, $filter->getFilterType());

        $filter = new Filter("name", [33, 44]);
        $this->assertEquals(FilterType::in, $filter->getFilterType());

        $filter = new Filter("name", [true, false]);
        $this->assertEquals(FilterType::in, $filter->getFilterType());


    }

}