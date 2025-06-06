<?php


namespace Kinintel\Test\ValueObjects\Dataset;

use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";


class FieldTest extends \PHPUnit\Framework\TestCase {

    public function testCanEvaluateValueExpressionsIncorporatingFieldValuesFromDataSetInBrackets() {

        $field = new Field("name", "Name", "Hello [[text]] World");
        $this->assertEquals("Hello My World", $field->evaluateValueExpression([
            "text" => "My"
        ]));

    }


    public function testCanEvaluateValueExpressionIncorporatingNestedFieldValuesFromDataSet() {

        $field = new Field("name", "Name", "Hello [[object.text]] World");

        $this->assertEquals("Hello My World", $field->evaluateValueExpression([
            "object" => ["text" => "My"]
        ]));

    }


    public function testCanEvaluateValueExpressionsIncorporatingRegularExpressionsUsingColonSyntax() {

        $field = new Field("name", "Name", "Hello [[text | /([y])/]] World");
        $this->assertEquals("Hello y World", $field->evaluateValueExpression([
            "text" => "My World"
        ]));

    }

    public function testIfTheValueExpressionOnNullValueOnlyFlagSetTheExpressionIsOnlyEvaluatedIfNullReturnedForUnderlyingFieldValue() {

        $field = new Field("name", "Name", "Hello [[text | /([y])/]] World", Field::TYPE_STRING, false, false, false, true);
        $this->assertEquals("Hello y World", $field->evaluateValueExpression([
            "text" => "My World"
        ]));

        $this->assertEquals("Bingo", $field->evaluateValueExpression([
            "text" => "My world",
            "name" => "Bingo"
        ]));


    }


    public function testCanGetPlainFieldsFromArrayOfFields() {

        $field1 = new Field("name", "Name", "Hello [[object.text]] World", Field::TYPE_INTEGER, true);
        $field2 = new Field("address", "Address", "[[object.address]]", Field::TYPE_STRING);
        $field3 = new Field("phone", "Phone", "[[object.phone]]", Field::TYPE_INTEGER);

        $plainFields = Field::toPlainFields([$field1, $field2, $field3]);
        $this->assertEquals([new Field("name", "Name", null, Field::TYPE_INTEGER, true),
            new Field("address", "Address", null, Field::TYPE_STRING),
            new Field("phone", "Phone", null, Field::TYPE_INTEGER)], $plainFields);


    }


    public function testCanGetPlainFieldsWithRemovedKeyFieldsIfBooleanPassed() {
        $field1 = new Field("name", "Name", "Hello [[object.text]] World", Field::TYPE_INTEGER, true);
        $field2 = new Field("address", "Address", "[[object.address]]", Field::TYPE_STRING, true);
        $field3 = new Field("phone", "Phone", "[[object.phone]]", Field::TYPE_INTEGER);

        $plainFields = Field::toPlainFields([$field1, $field2, $field3], true);
        $this->assertEquals([new Field("name", "Name", null, Field::TYPE_INTEGER),
            new Field("address", "Address", null, Field::TYPE_STRING),
            new Field("phone", "Phone", null, Field::TYPE_INTEGER)], $plainFields);
    }

}