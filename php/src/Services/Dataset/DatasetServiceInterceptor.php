<?php

namespace Kinintel\Services\Dataset;

use Kiniauth\Services\Application\ActivityLogger;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kinikit\Core\DependencyInjection\ContainerInterceptor;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;

/**
 * Sharing interceptor for whitelisting access to other resources
 */
class DatasetServiceInterceptor extends ContainerInterceptor {

    // Dataset account depths
    private array $datasetAccountDepths = [];


    /**
     * Construct with active record interceptor and dataset service for reference
     *
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     * @param ActivityLogger $activityLogger
     */
    public function __construct(private ActiveRecordInterceptor $activeRecordInterceptor,
                                private ActivityLogger          $activityLogger) {
    }


    /**
     * Intercept before method calls
     *
     * @param $objectInstance
     * @param $methodName
     * @param $params
     * @param $methodInspector
     * @return mixed
     */
    public function beforeMethod($objectInstance, $methodName, $params, $methodInspector) {

        if (($methodName == "getEvaluatedDataSetForDataSetInstance")) {
            $datasetInstance = $params["dataSetInstance"] ?? null;

            // Upgrade the dataset instance if required
            if (!($datasetInstance instanceof DatasetInstance) && ($datasetInstance instanceof DatasetInstanceSummary)) {
                if ($datasetInstance->getId()) {
                    $datasetInstance = $objectInstance->getFullDataSetInstance($datasetInstance->getId());
                    $params["dataSetInstance"] = $datasetInstance;
                }
            }
            if ($datasetInstance instanceof DatasetInstance) {
                $this->datasetAccountDepths[$datasetInstance->getAccountId()] = (isset($this->datasetAccountDepths[$datasetInstance->getAccountId()]) ?
                    $this->datasetAccountDepths[$datasetInstance->getAccountId()] + 1 : 1);
            }

        }

        return $params;
    }


    /**
     * Interceptor for trapping calls to evaluate and transformation
     * methods to whitelist access to other account resources
     *
     * @param $callable
     * @param $objectInstance
     * @param $methodName
     * @param $params
     * @param $methodInspector
     *
     * @return Callable
     */
    public function methodCallable($callable, $objectInstance, $methodName, $params, $methodInspector) {

        $accountId = null;
        if (($methodName == "getEvaluatedDataSetForDataSetInstance") ||
            ($methodName == "getTransformedDatasourceForDataSetInstance") ||
            ($methodName == "getEvaluatedParameters")) {
            $datasetInstance = $params["dataSetInstance"] ?? null;


            // Upgrade the dataset instance if required
            if (!($datasetInstance instanceof DatasetInstance) && ($datasetInstance instanceof DatasetInstanceSummary)) {
                if ($datasetInstance->getId())
                    $datasetInstance = $objectInstance->getFullDataSetInstance($datasetInstance->getId());
            }
            if ($datasetInstance instanceof DatasetInstance) {
                $accountId = $datasetInstance->getAccountId();
            }
        }

        // If an account id, return a new callback with whitelisted read access.
        if ($accountId) {
            return function () use ($callable, $accountId) {
                return $this->activeRecordInterceptor->executeWithWhitelistedAccountReadAccess($callable, $accountId);
            };
        }

        return $callable;
    }

    /**
     * After method hook - used for logging queries and associated items.
     *
     * @param $objectInstance
     * @param $methodName
     * @param $params
     * @param $returnValue
     * @param $methodInspector
     */
    public function afterMethod($objectInstance, $methodName, $params, $returnValue, $methodInspector) {
        $this->processDatasetQueryResult($methodName, $params["dataSetInstance"] ?? null, true);
        return $returnValue;
    }

    /**
     * On exception  method hook - used for logging queries and associated items
     *
     * @param $objectInstance
     * @param $methodName
     * @param $params
     * @param $exception
     * @param $methodInspector
     */
    public function onException($objectInstance, $methodName, $params, $exception, $methodInspector) {
        $this->processDatasetQueryResult($methodName, $params["dataSetInstance"] ?? null, false, $exception->getMessage());
    }


    /**
     * @param $methodName
     * @param $dataSetInstance
     * @return void
     */
    private function processDatasetQueryResult($methodName, $dataSetInstance, $success, $errorMessage = null): void {

        // If one of our target methods, log the query if required.
        if (($methodName == "getEvaluatedDataSetForDataSetInstance") ||
            ($methodName == "getTransformedDatasourceForDataSetInstance")) {

            $transactionId = date("U");

            $data = ["result" => $success ? "Success" : "Error"];
            if (!$success)
                $data["errorMessage"] = $errorMessage;

            if ($dataSetInstance && $dataSetInstance->getId()) {

                // If single depth, log the query
                $level = $this->datasetAccountDepths[$dataSetInstance->getAccountId()] ?? 0;
                if ($level < 2) {

                    $this->activityLogger->createLog("Dataset Query", $dataSetInstance->getId(),
                        $dataSetInstance->getTitle(), $data,
                        $transactionId);
                }

                // If level more than one don't log.
                if ($level > 0) {
                    $this->datasetAccountDepths[$dataSetInstance->getAccountId()]--;
                }


            }
        }
    }


}