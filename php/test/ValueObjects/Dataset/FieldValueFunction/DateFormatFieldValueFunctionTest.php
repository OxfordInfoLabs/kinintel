<?php


namespace Kinintel\Test\ValueObjects\Dataset\FieldValueFunction;


use Kinintel\ValueObjects\Dataset\FieldValueFunction\DateFormatFieldValueFunction;


include_once "autoloader.php";

class DateFormatFieldValueFunctionTest extends \PHPUnit\Framework\TestCase {

    public function testFunctionIsResolvedForKnownFunctionNames() {

        $function = new DateFormatFieldValueFunction();
        $this->assertFalse($function->doesFunctionApply("imaginary"));
        $this->assertFalse($function->doesFunctionApply("test"));

        $this->assertTrue($function->doesFunctionApply("dateConvert 'd/m/y' 'Y-m-d'"));
        $this->assertTrue($function->doesFunctionApply("ensureDateFormat 'd/m/Y'"));
        $this->assertTrue($function->doesFunctionApply("dayOfMonth"));
        $this->assertTrue($function->doesFunctionApply("dayOfWeek"));
        $this->assertTrue($function->doesFunctionApply("dayName"));
        $this->assertTrue($function->doesFunctionApply("monthName"));
        $this->assertTrue($function->doesFunctionApply("month"));
        $this->assertTrue($function->doesFunctionApply("year"));

    }


    public function testCanEnsureDateFormat(){
        $function = new DateFormatFieldValueFunction();
        $this->assertEquals('2020-01-01', $function->applyFunction("ensureDateFormat 'Y-m-d'", "2020-01-01", []));
        $this->assertNull($function->applyFunction("ensureDateFormat 'Y-m-d'", "01/01/2020", []));
    }

    public function testCanConvertDatesUsingDateConvert() {
        $function = new DateFormatFieldValueFunction();
        $this->assertEquals('01/01/2020', $function->applyFunction("dateConvert 'Y-m-d' 'd/m/Y'", "2020-01-01", []));
        $this->assertEquals('01/01/2020 10:44:33', $function->applyFunction("dateConvert 'Y-m-d H:i:s' 'd/m/Y H:i:s'", "2020-01-01 10:44:33", []));
        $this->assertNull($function->applyFunction("dateConvert 'Y-m-d' 'd/m/Y'", "Invalid", []));
    }


    public function testCanGetDayOfMonthForWholeSQLDateOrDateTime() {

        $function = new DateFormatFieldValueFunction();
        $this->assertEquals(25, $function->applyFunction("dayOfMonth", "2020-03-25", []));
        $this->assertEquals(25, $function->applyFunction("dayOfMonth", "2020-03-25 10:00:00", []));
        $this->assertEquals("05", $function->applyFunction("dayOfMonth", "2020-03-05 10:00:00", []));
    }

    public function testCanGetDayOfWeekForWholeSQLDateOrDateTime() {

        $function = new DateFormatFieldValueFunction();
        $this->assertEquals(5, $function->applyFunction("dayOfWeek", "2021-12-02", []));
        $this->assertEquals(5, $function->applyFunction("dayOfWeek", "2021-12-02 10:00:00", []));

    }

    public function testCanGetDayNameForWholeSQLDateOrDateTime() {

        $function = new DateFormatFieldValueFunction();
        $this->assertEquals("Thursday", $function->applyFunction("dayName", "2021-12-02", []));
        $this->assertEquals("Thursday", $function->applyFunction("dayName", "2021-12-02 10:00:00", []));

    }

    public function testCanGetMonthForWholeSQLDateOrDateTime() {

        $function = new DateFormatFieldValueFunction();
        $this->assertEquals(12, $function->applyFunction("month", "2021-12-02", []));
        $this->assertEquals("01", $function->applyFunction("month", "2021-01-02 10:00:00", []));

    }

    public function testCanGetMonthNameForWholeSQLDateOrDateTimeOrInteger() {

        $function = new DateFormatFieldValueFunction();
        $this->assertEquals("December", $function->applyFunction("monthName", "2021-12-02", []));
        $this->assertEquals("January", $function->applyFunction("monthName", "2021-01-02 10:00:00", []));
        $this->assertEquals("February", $function->applyFunction("monthName", 2, []));
    }

    public function testCanGetYearForWholeSQLDateOrDateTime() {

        $function = new DateFormatFieldValueFunction();
        $this->assertEquals(2021, $function->applyFunction("year", "2021-12-02", []));

    }

}