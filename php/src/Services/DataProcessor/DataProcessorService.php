<?php


namespace Kinintel\Services\DataProcessor;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Core\Validation\Validator;
use Kinintel\Exception\InvalidDataProcessorConfigException;
use Kinintel\Exception\InvalidDataProcessorTypeException;

class DataProcessorService {

    /**
     * @var DataProcessorDAO
     */
    private $dataProcessorDAO;


    /**
     * @var ObjectBinder
     */
    private $objectBinder;


    /**
     * @var Validator
     */
    private $validator;

    /**
     * DataProcessorService constructor.
     *
     * @param DataProcessorDAO $dataProcessorDAO
     * @param ObjectBinder $objectBinder
     * @param Validator $validator
     */
    public function __construct($dataProcessorDAO, $objectBinder, $validator) {
        $this->dataProcessorDAO = $dataProcessorDAO;
        $this->objectBinder = $objectBinder;
        $this->validator = $validator;
    }

    /**
     * Process a data processor instance using related processor
     *
     * @param string $instanceKey
     */
    public function processDataProcessorInstance($instanceKey) {

        // Get the instance ready for evaluation
        $instance = $this->dataProcessorDAO->getDataProcessorInstanceByKey($instanceKey);

        // Validate the instance and throw more specific exceptions if required
        $validationErrors = $instance->validate();
        if (sizeof($validationErrors)) {
            if (isset($validationErrors["type"]["invalidtype"])) {
                throw new InvalidDataProcessorTypeException($instance->getType());
            } else if (isset($validationErrors["config"])) {
                throw new InvalidDataProcessorConfigException($validationErrors);
            } else {
                throw new ValidationException($validationErrors);
            }
        }


        $instance->process();
    }


}