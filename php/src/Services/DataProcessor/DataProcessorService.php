<?php


namespace Kinintel\Services\DataProcessor;


use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Binding\ObjectBinder;
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
     * DataProcessorService constructor.
     *
     * @param DataProcessorDAO $dataProcessorDAO
     * @param ObjectBinder $objectBinder
     * @param Validator $validator
     * @param SecurityService $securityService
     * @param AccountService $accountService
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     */
    public function __construct($dataProcessorDAO, $objectBinder, $validator, $securityService, $accountService, $activeRecordInterceptor) {
        $this->dataProcessorDAO = $dataProcessorDAO;
        $this->objectBinder = $objectBinder;
        $this->validator = $validator;
        $this->securityService = $securityService;
        $this->accountService = $accountService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
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

        $this->activeRecordInterceptor->executeInsecure(function () use ($instance) {

            // If an account id, login as account id.
            if ($instance->getAccountId()) {
                $account = $this->accountService->getAccount($instance->getAccountId());
                $this->securityService->login(null, $account);
            } else {
                $this->securityService->loginAsSuperUser();
            }

        });


        $instance->process();
    }


}