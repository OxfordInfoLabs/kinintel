<?php


namespace Kinintel\Objects\DataProcessor;


use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Core\Validation\Validator;
use Kinintel\Services\DataProcessor\DataProcessor;

/**
 * Data processor instance
 *
 * @table ki_dataprocessor_instance
 * @generate
 */
class DataProcessorInstance extends DataProcessorInstanceSummary
{

    // use account project trait
    use AccountProject;


    /**
     * Type for this data source - can either be a mapping implementation key
     * or a fully qualified class path
     *
     * @var string
     */
    private $type;

    /**
     * @var mixed
     * @json
     */
    private $config;

    /**
     * DataProcessorInstance constructor.
     * @param string $type
     * @param mixed $config
     */
    public function __construct($key, $title, $type, $config = [], $projectKey = null, $accountId = null)
    {
        parent::__construct($key, $title);

        $this->type = $type;
        $this->config = $config;
        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }


    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }


    /**
     * Validate particularly the configuration according to the related
     * data processor
     */
    public function validate()
    {
        try {
            $this->returnProcessorAndConfig();
            return [];
        } catch (ValidationException $e) {
            return $e->getValidationErrors();
        }
    }


    /**
     * Process using the underlying processor
     */
    public function process()
    {
        list ($processor, $config) = $this->returnProcessorAndConfig();
        $processor->process($this);
    }


    /**
     * Return config
     *
     * @return mixed
     */
    public function returnConfig()
    {
        list ($processor, $config) = $this->returnProcessorAndConfig();
        return $config;
    }

    // Return processor and config
    private function returnProcessorAndConfig()
    {
        $validationErrors = [];
        $dataProcessor = null;
        $config = [];
        try {

            $dataProcessor = Container::instance()->getInterfaceImplementation(DataProcessor::class, $this->getType());
            $objectBinder = Container::instance()->get(ObjectBinder::class);
            $validator = Container::instance()->get(Validator::class);

            // If a config class, map it and validate
            if ($dataProcessor->getConfigClass()) {

                if (is_object($this->config)) {
                    $config = $this->config;
                } else {
                    $config = $objectBinder->bindFromArray($this->getConfig(), $dataProcessor->getConfigClass());
                }

                if ($validator->validateObject($config))
                    $validationErrors["config"] = $validator->validateObject($config);
            }


        } catch (MissingInterfaceImplementationException $e) {
            $validationErrors["type"] = [
                "invalidtype" => new FieldValidationError("type", "invalidtype", "The data processor of type '{$this->type}' does not exists")
            ];
        }

        if (sizeof($validationErrors)) {
            throw new ValidationException($validationErrors);
        } else {
            return [$dataProcessor, $config];
        }


    }


}