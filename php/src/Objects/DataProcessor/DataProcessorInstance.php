<?php


namespace Kinintel\Objects\DataProcessor;


use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Core\Validation\Validator;
use Kinintel\Objects\Dataset\DatasetInstanceLabel;
use Kinintel\Services\DataProcessor\DataProcessor;

/**
 * Data processor instance
 *
 * @table ki_dataprocessor_instance
 * @generate
 * @interceptor \Kinintel\Objects\DataProcessor\DataProcessorInstanceInterceptor
 */
class DataProcessorInstance extends DataProcessorInstanceSummary {

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
     * @sqlType LONGTEXT
     * @json
     */
    private $config;


    /**
     * Trigger (either adhoc or scheduled).
     *
     * @var string
     * @values adhoc,scheduled
     */
    private $trigger;

    /**
     * @var ScheduledTask
     * @manyToOne
     * @parentJoinColumns scheduled_task_id
     * @saveCascade
     * @deleteCascade
     */
    private $scheduledTask;


    /**
     * Related object type if relevant
     *
     * @var string
     * @values DatasetInstance,DatasourceInstance,Feed
     */
    private $relatedObjectType;


    /**
     * Key for the related object if relevant.  This is type specific
     * e.g. Management Key in case of query.
     *
     * @var string
     */
    private $relatedObjectKey;


    /**
     * @var DatasetInstanceLabel
     * @readOnly
     * @manyToOne
     * @parentJoinColumns related_object_key
     */
    private $relatedDataSetInstance;


    // Related object type constants
    const RELATED_OBJECT_TYPE_DATASET_INSTANCE = "DatasetInstance";
    const RELATED_OBJECT_TYPE_DATASOURCE_INSTANCE = "DatasourceInstance";
    const RELATED_OBJECT_TYPE_FEED = "Feed";


    // Trigger constants for whether this is adhoc or a scheduled processor.
    const TRIGGER_ADHOC = "adhoc";
    const TRIGGER_SCHEDULED = "scheduled";


    /**
     * DataProcessorInstance constructor.
     * @param string $relatedObjectKey
     * @param string $relatedObjectType
     * @param ScheduledTask $scheduledTask
     * @param string $trigger
     * @param mixed $config
     * @param string $type
     */
    public function __construct($key, $title, $type, $config = [], $trigger = self::TRIGGER_ADHOC, $scheduledTask = null, $relatedObjectType = null, $relatedObjectKey = null,
                                $projectKey = null, $accountId = null, $accountSummary = null) {
        parent::__construct($key, $title);

        $this->type = $type;
        $this->config = $config;
        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
        $this->trigger = $trigger;
        $this->scheduledTask = $scheduledTask;
        $this->relatedObjectType = $relatedObjectType;
        $this->relatedObjectKey = $relatedObjectKey;
        $this->accountSummary = $accountSummary;
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
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTrigger() {
        return $this->trigger;
    }

    /**
     * @param string $trigger
     */
    public function setTrigger($trigger) {
        $this->trigger = $trigger;
    }

    /**
     * @return ScheduledTask
     */
    public function getScheduledTask() {
        return $this->scheduledTask;
    }

    /**
     * @param ScheduledTask $scheduledTask
     */
    public function setScheduledTask($scheduledTask) {
        $this->scheduledTask = $scheduledTask;
    }

    /**
     * @return string
     */
    public function getRelatedObjectType() {
        return $this->relatedObjectType;
    }

    /**
     * @param string $relatedObjectType
     */
    public function setRelatedObjectType($relatedObjectType) {
        $this->relatedObjectType = $relatedObjectType;
    }

    /**
     * @return string
     */
    public function getRelatedObjectKey() {
        return $this->relatedObjectKey;
    }

    /**
     * @param string $relatedObjectKey
     */
    public function setRelatedObjectKey($relatedObjectKey) {
        $this->relatedObjectKey = $relatedObjectKey;
    }


    /**
     * Get related object title if available
     *
     * @return string
     */
    public function getRelatedObjectTitle() {
        switch ($this->getRelatedObjectType()) {
            case self::RELATED_OBJECT_TYPE_DATASET_INSTANCE:
                return $this->relatedDataSetInstance?->getTitle();
            default:
                return "";
        }
    }

    /**
     * Validate particularly the configuration according to the related
     * data processor
     */
    public function validate() {
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
    public function process() {
        list ($processor, $config) = $this->returnProcessorAndConfig();
        $processor->process($this);
    }


    /**
     * Return config
     *
     * @return mixed
     */
    public function returnConfig() {
        list ($processor, $config) = $this->returnProcessorAndConfig();
        return $config;
    }


    /**
     * @return DataProcessor
     * @throws ValidationException
     */
    public function returnProcessor() {
        list ($processor, $config) = $this->returnProcessorAndConfig();
        return $processor;
    }

    // Return processor and config
    private function returnProcessorAndConfig() {
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