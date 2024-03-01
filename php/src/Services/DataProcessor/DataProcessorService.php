<?php


namespace Kinintel\Services\DataProcessor;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Core\Validation\Validator;
use Kinintel\Exception\InvalidDataProcessorConfigException;
use Kinintel\Exception\InvalidDataProcessorTypeException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\ValueObjects\DataProcessor\DataProcessorItem;

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
     * @var SecurityService
     */
    private $securityService;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * @var ActiveRecordInterceptor
     */
    private $activeRecordInterceptor;

    /**
     * @var ScheduledTaskService
     */
    private $scheduledTaskService;

    /**
     * DataProcessorService constructor.
     *
     * @param DataProcessorDAO $dataProcessorDAO
     * @param ObjectBinder $objectBinder
     * @param Validator $validator
     * @param SecurityService $securityService
     * @param AccountService $accountService
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     * @param ScheduledTaskService $scheduledTaskService
     */
    public function __construct($dataProcessorDAO, $objectBinder, $validator, $securityService, $accountService, $activeRecordInterceptor, $scheduledTaskService) {
        $this->dataProcessorDAO = $dataProcessorDAO;
        $this->objectBinder = $objectBinder;
        $this->validator = $validator;
        $this->securityService = $securityService;
        $this->accountService = $accountService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->scheduledTaskService = $scheduledTaskService;
    }


    /**
     * Get a data processor instance by instance key
     *
     * @param $instanceKey
     * @return void
     */
    public function getDataProcessorInstance($instanceKey) {

    }


    public function filterDataProcessorInstances() {

    }


    /**
     * Save data processor from item and optional account and project key
     *
     * @param DataProcessorItem $dataProcessorItem
     * @param string $projectKey
     * @param int $accountId
     *
     * @return string
     */
    public function saveDataProcessorInstance($dataProcessorItem, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Handle new and existing cases
        if ($dataProcessorItem->getKey()) {
            $key = $dataProcessorItem->getKey();

            // Grab existing instance
            $existingInstance = $this->dataProcessorDAO->getDataProcessorInstanceByKey($key);

            // Grab scheduled task
            $scheduledTask = $existingInstance->getScheduledTask();

        } else {
            $key = $dataProcessorItem->getType() . "_" . ($accountId ?? 0) . "_" . date("U");
            $scheduledTask = new ScheduledTask(
                new ScheduledTaskSummary("dataprocessor", $key, ["dataProcessorKey" => $key], []), $projectKey, $accountId);
        }

        // Update the scheduled task
        if ($dataProcessorItem->getTrigger() == DataProcessorInstance::TRIGGER_SCHEDULED) {
            $scheduledTask->setTimePeriods($dataProcessorItem->getTaskTimePeriods() ?? []);
        } else {
            $scheduledTask->setTimePeriods([]);
            $scheduledTask->setNextStartTime(null);
        }

        // Create a processor
        $instance = new DataProcessorInstance($key, $dataProcessorItem->getTitle(),
            $dataProcessorItem->getType(), $dataProcessorItem->getConfig(),
            $dataProcessorItem->getTrigger(), $scheduledTask, null, null, $projectKey, $accountId);

        // Validate first and throw accordingly
        $this->validateProcessor($instance);

        // Save the processor
        $this->dataProcessorDAO->saveProcessorInstance($instance);

        return $key;
    }


    /**
     * Trigger a data processor instance using the passed key.  This is mostly useful for user created
     * data processors.
     *
     * @param $instanceKey
     * @return void
     */
    public function triggerDataProcessorInstance($instanceKey) {

        // Get the data processor
        $dataProcessor = $this->dataProcessorDAO->getDataProcessorInstanceByKey($instanceKey);

        // Trigger the scheduled task
        if ($dataProcessor->getScheduledTask()) {
            $this->scheduledTaskService->triggerScheduledTask($dataProcessor->getScheduledTask()->getId());
        }
    }


    /**
     * Remove a data processor instance by instance key.
     *
     * @param $instanceKey
     * @return void
     */
    public function removeDataProcessorInstance($instanceKey) {
        $this->dataProcessorDAO->removeProcessorInstance($instanceKey);
    }


    /**
     * Process a data processor instance using related processor
     *
     * @param string $instanceKey
     */
    public function processDataProcessorInstance($instanceKey) {

        // Get the instance ready for evaluation
        $instance = $this->dataProcessorDAO->getDataProcessorInstanceByKey($instanceKey);
        $this->processDataProcessorInstanceObject($instance);

    }

    /**
     * @param DataProcessorInstance $instance
     * @return void
     * @throws InvalidDataProcessorConfigException
     * @throws InvalidDataProcessorTypeException
     * @throws ValidationException
     * @throws \Throwable
     */
    public function processDataProcessorInstanceObject($instance) {
        // Validate the instance and throw more specific exceptions if required
        $this->validateProcessor($instance);

        $this->activeRecordInterceptor->executeInsecure(function () use ($instance) {

            // If an account id, login as account id.
            if ($instance->getAccountId()) {
                $this->securityService->becomeAccount($instance->getAccountId());
            } else {
                $this->securityService->becomeSuperUser();
            }

        });


        $instance->process();
    }


    /**
     * @param DataProcessorInstance $instance
     * @return void
     * @throws InvalidDataProcessorConfigException
     * @throws InvalidDataProcessorTypeException
     * @throws ValidationException
     */
    private function validateProcessor(DataProcessorInstance $instance) {
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
    }


}