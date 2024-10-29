<?php

namespace Kinintel\Test\ValueObjects\Transformation;

use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;

include_once "autoloader.php";

class ColumnsTransformationTest extends TestBase {

    public function testAlteredColumnsWithoutResettingNames(){
        $transformation = new ColumnsTransformation([
            new Field("name", "Forename")
        ], resetColumnNames: false);
        $altered = $transformation->returnAlteredColumns([
            new Field("name"),
        ]);

        $this->assertEquals([new Field("name", "Forename")], $altered);

        $altered = $transformation->returnAlteredColumns([
            new Field("name", "First Name"),
            new Field("age", "Age")
        ]);

        $this->assertEquals([new Field("name", "Forename")], $altered);
    }
    public function testAlteredColumnsResettingNames(){
        $transformation = new ColumnsTransformation([
            new Field("name", "Forename")
        ], resetColumnNames: true);
        $altered = $transformation->returnAlteredColumns([
            new Field("name"),
        ]);

        $this->assertEquals([new Field("forename", "Forename")], $altered);

        $altered = $transformation->returnAlteredColumns([
            new Field("name", "First Name"),
            new Field("age", "Age")
        ]);

        $this->assertEquals([new Field("forename", "Forename")], $altered);
    }

    public function testAlteredColumnsWithMultipleColumnsResettingNames(){
        $transformation = new ColumnsTransformation([
            new Field("name", "Left Name"),
            new Field("forename", "Right Name"),
        ], resetColumnNames: true);

        $initialColumns = [
            new Field("name", "Name"),
            new Field("name_2", "Forename")
        ];

        $altered = $transformation->returnAlteredColumns($initialColumns);

        $this->assertEquals([
            new Field("leftName", "Left Name"),
            new Field("forename", "Right Name"),
        ], $altered);
    }

    public function testDuplicateColumnsWithSameTitle(){
        $transformation = new ColumnsTransformation([
            new Field("a", "Left"),
            new Field("b", "Left"),
        ], resetColumnNames: true);

        $initialColumns = [
            new Field("a", "Left"),
            new Field("b", "Right")
        ];

        $altered = $transformation->returnAlteredColumns($initialColumns);
        $expected = [
            new Field("left", "Left"),
            new Field("left2", "Left")
        ];
        $this->assertEquals($expected, $altered);
    }
}