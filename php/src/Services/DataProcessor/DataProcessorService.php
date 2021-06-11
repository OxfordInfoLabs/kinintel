<?php


namespace Kinintel\Services\DataProcessor;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
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

        try {

            /**
             * @var DataProcessor $dataProcessor
             */
            $dataProcessor = Container::instance()->getInterfaceImplementation(DataProcessor::class, $instance->getType());

            // If a config class, map it and validate
            if ($dataProcessor->getConfigClass()) {
                $config = $this->objectBinder->bindFromArray($instance->getConfig(), $dataProcessor->getConfigClass());
                if ($validationErrors = $this->validator->validateObject($config)) {
                    throw new InvalidDataProcessorConfigException($validationErrors);
                }
            }

            // Actually process this data processor
            $dataProcessor->process($config ?? null);


        } catch (MissingInterfaceImplementationException $e) {
            throw new InvalidDataProcessorTypeException($instance->getType());
        }
    }


}