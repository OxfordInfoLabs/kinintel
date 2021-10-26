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


    public function testCanEvaluateValueExpressionsIncorporatingRegularExpressionsUsingColonSyntax() {

        $field = new Field("name", "Name", "Hello [[text:/([y])/]] World");
        $this->assertEquals("Hello y World", $field->evaluateValueExpression([
            "text" => "My World"
        ]));

    }


    public function testCanEvaluateValueExpressionIncorporatingNestedFieldValuesFromDataSet() {

        $field = new Field("name", "Name", "Hello [[object.text]] World");

        $this->assertEquals("Hello My World", $field->evaluateValueExpression([
            "object" => ["text" => "My"]
        ]));

    }

}