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

    // Overall query depth
    private $totalQueryDepth = 0;

    // Full dataset instances
    private $fullDatasetInstances = [];

    // Dataset account depths
    private array $datasetAccountQueryDepths = [];

    // Transaction id
    private int $transactionId = 0;


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


        if (($methodName == "getEvaluatedDataSetForDataSetInstance") || ($methodName == "getTransformedDatasourceForDataSetInstance")) {
            $datasetInstance = $params["dataSetInstance"] ?? null;

            // Upgrade the dataset instance if required
            if (!($datasetInstance instanceof DatasetInstance) && ($datasetInstance instanceof DatasetInstanceSummary)) {
                if ($datasetInstance->getId() && !isset($this->fullDatasetInstances[$datasetInstance->getId()])) {
                    $datasetInstance = $this->fullDatasetInstances[$datasetInstance->getId()] = $objectInstance->getFullDataSetInstance($datasetInstance->getId());
                }
            }
            // Record depth and create transaction id if not a join scenario
            if (($datasetInstance instanceof DatasetInstance) && ($methodName == "getEvaluatedDataSetForDataSetInstance")) {
                if ($this->totalQueryDepth == 0) {
                    $this->transactionId = date("U");
                }
                $this->totalQueryDepth++;
                $this->datasetAccountQueryDepths[$datasetInstance->getAccountId()] = (isset($this->datasetAccountQueryDepths[$datasetInstance->getAccountId()]) ?
                    $this->datasetAccountQueryDepths[$datasetInstance->getAccountId()] + 1 : 1);
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
            ($methodName == "getEvaluatedParameters") ||
            ($methodName == "getDatasetTree")) {


            $datasetInstance = $params["dataSetInstance"] ?? null;


            if (($datasetInstance instanceof DatasetInstanceSummary) && $datasetInstance->getId()) {

                if (!isset($this->fullDatasetInstances[$datasetInstance->getId()])) {

                    $this->fullDatasetInstances[$datasetInstance->getId()] = ($datasetInstance instanceof DatasetInstance) ? $datasetInstance :
                        $objectInstance->getFullDataSetInstance($datasetInstance->getId());
                }

                $accountId = $this->fullDatasetInstances[$datasetInstance->getId()]->getAccountId();
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

            if ($methodName == "getEvaluatedDataSetForDataSetInstance") {
                $this->totalQueryDepth--;
            }

            $data = ["result" => $success ? "Success" : "Error"];
            if (!$success)
                $data["errorMessage"] = $errorMessage;

            if (($dataSetInstance instanceof DatasetInstanceSummary) && $dataSetInstance->getId()) {

                // Grab full dataset instance
                $fullDatasetInstance = ($dataSetInstance instanceof DatasetInstance) ? $dataSetInstance : $this->fullDatasetInstances[$dataSetInstance->getId()];

                // If single depth, log the query
                $level = $this->datasetAccountQueryDepths[$fullDatasetInstance->getAccountId()] ?? 0;
                if ($level < 2) {

                    $this->activityLogger->createLog("Dataset Query", $dataSetInstance->getId(),
                        $dataSetInstance->getTitle(), $data,
                        $this->transactionId);
                }

                // If level more than one don't log.
                if (($level > 0) && ($methodName == "getEvaluatedDataSetForDataSetInstance")) {
                    $this->datasetAccountQueryDepths[$fullDatasetInstance->getAccountId()]--;
                }


            }
        }
    }


}