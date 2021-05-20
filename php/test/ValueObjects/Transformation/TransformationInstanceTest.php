<?php

namespace Kinintel\ValueObjects\Transformation;

use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Exception\InvalidTransformationTypeException;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;

include_once "autoloader.php";

class TransformationInstanceTest extends \PHPUnit\Framework\TestCase {


    public function testExceptionRaisedOnReturnIfInvalidTransformationTypeSuppliedToInstance() {

        try {
            $transformationInstance = new TransformationInstance("badtype", []);
            $transformationInstance->returnTransformation();
            $this->fail("Should have thrown here");
        } catch (InvalidTransformationTypeException $e) {
            $this->assertTrue(true);
        }

    }


    public function testExceptionRaisedOnReturnIfTransformationConfigHasValidationErrors() {
        try {
            $transformationInstance = new TransformationInstance("Kinintel\ValueObjects\Transformation\TestTransformation", []);
            $transformationInstance->returnTransformation();
            $this->fail("Should have thrown here");
        } catch (InvalidTransformationConfigException $e) {
            $this->assertTrue(true);
        }
    }


    public function testCanReturnTransformationForValidConfig() {

        $transformationInstance = new TransformationInstance("filter", []);
        $this->assertEquals(new FilterTransformation(), $transformationInstance->returnTransformation());

    }

}