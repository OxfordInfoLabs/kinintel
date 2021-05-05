<?php


namespace Kinintel\ValueObjects\Transformation;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Validation\Validator;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Exception\InvalidTransformationTypeException;

class TransformationInstance {

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $config;

    /**
     * TransformationInstance constructor.
     *
     * @param string $type
     * @param mixed $config
     */
    public function __construct($type = null, $config = []) {
        $this->type = $type;
        $this->config = $config;
    }


    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config) {
        $this->config = $config;
    }


    /**
     * Return transformation for this instance
     *
     * @return Transformation
     */
    public function returnTransformation() {

        /**
         * @var ObjectBinder $objectBinder
         */
        $objectBinder = Container::instance()->get(ObjectBinder::class);

        /**
         * @var Validator $validator
         */
        $validator = Container::instance()->get(Validator::class);

        try {
            // Grab the transformation instance
            $instanceClass = Container::instance()->getInterfaceImplementationClass(Transformation::class, $this->getType());
        } catch (MissingInterfaceImplementationException $e) {
            throw new InvalidTransformationTypeException($this->getType());
        }

        // Bind data to transformation
        if (is_array($this->getConfig()))
            $transformation = $objectBinder->bindFromArray($this->getConfig(), $instanceClass);
        else
            $transformation = $this->getConfig();

        $validationErrors = $validator->validateObject($transformation);
        if (sizeof($validationErrors)) {
            throw new InvalidTransformationConfigException($validationErrors);
        }

        return $transformation;

    }


}