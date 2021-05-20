<?php


namespace Kinintel\Test\ValueObjects\Transformation;


use Kinintel\ValueObjects\Transformation\Transformation;

class AnotherTestTransformation implements Transformation {

    /**
     * @var string
     * @required
     */
    private $property;

    /**
     * @return string
     */
    public function getProperty() {
        return $this->property;
    }

    /**
     * @param string $property
     */
    public function setProperty($property) {
        $this->property = $property;
    }

}