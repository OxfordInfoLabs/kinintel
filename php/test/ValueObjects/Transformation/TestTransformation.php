<?php


namespace Kinintel\ValueObjects\Transformation;


class TestTransformation implements Transformation {

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