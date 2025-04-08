<?php


namespace Kinintel\Services\DataProcessor;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Core\Validation\Validator;
use Kinintel\Exception\InvalidDataProcessorConfigException;
use Kinintel\Exception\InvalidDataProcessorTypeException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;

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
     * @return DataProcessorInstance
     */
    public function getDataProcessorInstance($instanceKey) {
        return $this->dataProcessorDAO->getDataProcessorInstanceByKey($instanceKey);
    }


    /**
     * Filter data processor instances
     *
     * @param array $filters
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     *
     * @return DataProcessorInstance[]
     */
    public function filterDataProcessorInstances($filters = [], $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return $this->dataProcessorDAO->filterDataProcessorInstances($filters, $projectKey, $offset, $limit, $accountId);
    }


    /**
     * Save data processor from item and optional account and project key
     *
     * @param DataProcessorInstance $dataProcessorInstance
     *
     * @return string
     * @hasPrivilege PROJECT:dataprocessormanage($dataProcessorInstance.projectKey)
     */
    public function saveDataProcessorInstance($dataProcessorInstance) {


        // Validate the processor
        $this->validateProcessor($dataProcessorInstance);

        // Save the processor
        $this->dataProcessorDAO->saveProcessorInstance($dataProcessorInstance);


        return $dataProcessorInstance->getKey();
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

        if ($dataProcessor->getProjectKey() && !$this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_PROJECT, "dataprocessormanage", $dataProcessor->getProjectKey())) {
            throw new AccessDeniedException("You have not been granted access to manage data processors.");
        }

        // Trigger the scheduled task if one exists to run in the background
        if ($dataProcessor->getScheduledTask()) {
            $this->scheduledTaskService->triggerScheduledTask($dataProcessor->getScheduledTask()->getId());
        } // Otherwise run synchronously
        else {
            $this->processDataProcessorInstanceObject($dataProcessor);
        }
    }


    /**
     * Remove a data processor instance by instance key.
     *
     * @param $instanceKey
     * @return void
     */
    public function removeDataProcessorInstance($instanceKey) {


        // Get the data processor
        $dataProcessor = $this->dataProcessorDAO->getDataProcessorInstanceByKey($instanceKey);

        if ($dataProcessor->getProjectKey() && !$this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_PROJECT, "dataprocessormanage", $dataProcessor->getProjectKey())) {
            throw new AccessDeniedException("You have not been granted access to manage data processors.");
        }

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