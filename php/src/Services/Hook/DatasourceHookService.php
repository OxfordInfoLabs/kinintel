<?php

namespace Kinintel\Services\Hook;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;

class DatasourceHookService {

    public function __construct(
        private DataProcessorService    $dataProcessorService,
        private ScheduledTaskService    $scheduledTaskService,
        private ObjectBinder            $objectBinder,
        private ActiveRecordInterceptor $activeRecordInterceptor
    ) {
    }

    /**
     * @param int $hookId
     * @return DatasourceHookInstance
     */
    public function getDatasourceHookById($hookId) {
        return DatasourceHookInstance::fetch($hookId);
    }

    public function getDatasourceInstanceHooksForDatasource($datasourceInstanceKey) {
        return DatasourceHookInstance::filter("WHERE datasourceInstanceKey = ?", $datasourceInstanceKey);
    }

    public function getDatasourceHookInstancesForDatasourceInstanceAndMode($key, $mode) {
        return DatasourceHookInstance::filter("WHERE datasourceInstanceKey = ? AND (hookMode = ? OR hookMode = ?) AND enabled", $key, $mode, DatasourceHookInstance::HOOK_MODE_ALL);
    }


    /**
     * @param $datasourceKey
     * @param $updateMode
     * @param $data
     * @return void
     * @throws \Kinikit\Core\Exception\AccessDeniedException
     * @throws \Throwable
     */
    public function processHooks($datasourceKey, $updateMode, $data = []) {

        /** @var DatasourceHookInstance[] $hooks */
        $hooks = $this->getDatasourceHookInstancesForDatasourceInstanceAndMode($datasourceKey, $updateMode);


        // Process all applicable hooks
        foreach ($hooks as $hookInstance) {

            $executable = function () use ($hookInstance, $updateMode, $data) {

                // Check for processor based hooks
                if ($processorKey = $hookInstance->getDataProcessorInstanceKey()) {
                    $this->dataProcessorService->triggerDataProcessorInstance($processorKey);
                } // Check for scheduled task based hooks
                else if ($scheduledTaskId = $hookInstance->getScheduledTaskId()) {
                    $this->scheduledTaskService->triggerScheduledTask($scheduledTaskId);
                } else if ($hookKey = $hookInstance->getHookKey()) {

                    $hook = Container::instance()->getInterfaceImplementation(DatasourceHook::class, $hookKey);
                    $configClass = $hook->getConfigClass();
                    $hookConfig = is_a($hookInstance->getHookConfig(), $configClass) ?:
                        $this->objectBinder->bindFromArray($hookInstance->getHookConfig(), $configClass);

                    // Process the hook
                    $hook->processHook($hookConfig, $updateMode, $data);

                }
            };

            // Handle secure and insecure versions separately
            if ($hookInstance->isExecuteInsecure()) {
                $this->activeRecordInterceptor->executeInsecure($executable);
            } else {
                $executable();
            }

        }

    }

    /**
     * Delete a datasource hook instance
     *
     * @param int $hookId
     * @return void
     */
    public function deleteHook($hookId) {
        $hook = $this->getDatasourceHookById($hookId);
        $hook->remove();
    }

    /**
     * Save a hook instance
     *
     * @param DatasourceHookInstance $hookInstance
     */
    public function saveHookInstance($hookInstance, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $hookInstance->setProjectKey($projectKey);
        $hookInstance->setAccountId($accountId);

        $hookInstance->save();
        return $hookInstance->getId();

    }

    /**
     * Filter datasource hook instances
     *
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     *
     * @return DatasourceHookInstance[]
     */
    public function filterDatasourceHookInstances($projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $params = [];
        if ($accountId === null) {
            $whereClause = "WHERE accountId IS NULL";
        } else {
            $whereClause = "WHERE accountId = ?";
            $params[] = $accountId;
        }

        if ($projectKey) {
            $whereClause .= " AND project_key = ?";
            $params[] = $projectKey;
        }

        $whereClause .= " LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        // Return a summary array
        Logger::log($whereClause);
        return DatasourceHookInstance::filter($whereClause, $params);

    }
}