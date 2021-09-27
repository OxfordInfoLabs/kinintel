<?php


namespace Kinintel\Test\Objects\DataProcessor;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Test\Services\DataProcessor\TestDataProcessor;

include_once "autoloader.php";

class DataProcessorInstanceTest extends \PHPUnit\Framework\TestCase {

    public function testCanValidateProcessorInstanceBasedOnProcessorTypeAndConfig() {

        Container::instance()->addInterfaceImplementation(DataProcessor::class, "testprocessor", TestDataProcessor::class);

        $instance = new DataProcessorInstance("badprocessor", "My New One", "badprocessor");

        $validationErrors = $instance->validate();
        $this->assertEquals(1, sizeof($validationErrors));

        $this->assertEquals(new FieldValidationError("type", "invalidtype", "The data processor of type 'badprocessor' does not exists"),
            $validationErrors["type"]["invalidtype"]);


        $instance = new DataProcessorInstance("testprocessor", "Another one", "testprocessor");

        $validationErrors = $instance->validate();
        $this->assertEquals(1, sizeof($validationErrors));

        $this->assertEquals(new FieldValidationError("property", "required", "This field is required"),
            $validationErrors["config"]["property"]["required"]);

    }


}