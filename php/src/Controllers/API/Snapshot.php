<?php

namespace Kinintel\Controllers\API;

use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\ORM\Query\Filter\LikeFilter;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetSnapshotProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\DataProcessorItem;
use Kinintel\ValueObjects\DataProcessor\Snapshot\SnapshotDescriptor;
use Kinintel\ValueObjects\DataProcessor\Snapshot\SnapshotItem;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;
use League\Uri\Exception;

class Snapshot {

    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;


    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @param DatasetService $datasetService
     * @param DataProcessorService $dataProcessorService
     */
    public function __construct(DatasetService $datasetService, DataProcessorService $dataProcessorService, DatasourceService $datasourceService) {
        $this->datasetService = $datasetService;
        $this->dataProcessorService = $dataProcessorService;
        $this->datasourceService = $datasourceService;
    }

    /**
     * @return void
     */
    public function handleRequest() {
        throw new Exception("Invalid endpoint called");
    }


    /**
     * List snapshots for a given management key
     *
     * @http GET /$managementKey
     *
     * @return SnapshotItem[]
     */
    public function listSnapshotsForManagementKey($managementKey) {
        try {
            $dataset = $this->datasetService->getDatasetInstanceByManagementKey($managementKey);
        } catch (ObjectNotFoundException $e) {
            throw new ItemNotFoundException("No dataset exists with management key '$managementKey'");
        }

        $dataProcessors = $this->dataProcessorService->filterDataProcessorInstances(["type" =>
            new LikeFilter("type", "%snapshot%"),
            "relatedObjectType" => "DatasetInstance",
            "relatedObjectKey" => $dataset->getId()], null, 0, 1000000);

        return array_map(function ($processor) use ($dataset) {
            return SnapshotItem::fromDataProcessorAndDatasetInstances($processor, $dataset);
        }, $dataProcessors ?? []);

    }


    /**
     * Get a snapshot for a given management key and
     *
     * @http GET /$managementKey/$snapshotKey
     *
     * @param string $managementKey
     * @param string $snapshotKey
     *
     * @return SnapshotItem
     */
    public function getSnapshotForManagementKey($managementKey, $snapshotKey) {
        return $this->verifySnapshotKeyExistsForManagementKey($managementKey, $snapshotKey);
    }


    /**
     * Evaluate a snapshot for a management key, optionally limited and offset
     *
     * @http GET /$managementKey/$snapshotKey/data
     *
     * @param string $managementKey
     * @param string $snapshotKey
     * @param integer $limit
     * @param integer $offset
     *
     * @return Dataset
     */
    public function evaluateSnapshotForManagementKey($managementKey, $snapshotKey, $limit = 25, $offset = 0) {

        // Verify snapshot key exists
        $this->verifySnapshotKeyExistsForManagementKey($managementKey, $snapshotKey);

        // Return the evaluated datasource
        return $this->datasourceService->getEvaluatedDataSourceByInstanceKey($snapshotKey . "_latest", [], [], $offset, $limit);
    }


    /**
     * Create snapshot for management key
     *
     * @http POST /$managementKey
     *
     * @param string $managementKey
     * @param SnapshotDescriptor $snapshotDescriptor
     *
     * @return string
     */
    public function createSnapshotForManagementKey($managementKey, $snapshotDescriptor) {

        try {
            // Grab full data set instance
            $fullDataSetInstance = $this->datasetService->getFullDataSetInstanceByManagementKey($managementKey);

            // Map indexes to
            $indexes = array_map(fn($index) => new Index($index), $snapshotDescriptor->getIndexes() ?? []);

            // Create config.
            $config = new TabularDatasetSnapshotProcessorConfiguration([], [], $snapshotDescriptor->getParameterValues(), true, false, null, $indexes);

            // Create a snapshot item for convenience
            $snapshotItem = new DataProcessorItem($snapshotDescriptor->getTitle(), "tabulardatasetsnapshot", $config, DataProcessorInstance::TRIGGER_ADHOC, DataProcessorInstance::RELATED_OBJECT_TYPE_DATASET_INSTANCE,
                $fullDataSetInstance->getId());
            $processor = $snapshotItem->toDataProcessorInstance($fullDataSetInstance->getProjectKey(), $fullDataSetInstance->getAccountId());

            $newKey = $this->dataProcessorService->saveDataProcessorInstance($processor);

            // If run now, trigger snapshot now
            if ($snapshotDescriptor->isRunNow())
                $this->triggerSnapshot($managementKey, $newKey);

            return ["snapshotKey" => $newKey];

        } catch (ObjectNotFoundException $e) {
            throw new ItemNotFoundException("No dataset exists with management key '$managementKey'");
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException("The API key used does not have sufficient permissions to manage snapshots.");
        }

    }


    /**
     * Update snapshot for management key and snapshot key
     *
     * @http PUT /$managementKey/$snapshotKey
     *
     * @param string $managementKey
     * @param string $snapshotKey
     * @param SnapshotDescriptor $snapshotDescriptor
     *
     * @return string
     */
    public function updateSnapshotForManagementKey($managementKey, $snapshotKey, $snapshotDescriptor) {

        // Verify snapshot key exists
        $this->verifySnapshotKeyExistsForManagementKey($managementKey, $snapshotKey);

        // Grab existing snapshot
        $existingSnapshot = $this->dataProcessorService->getDataProcessorInstance($snapshotKey);

        $existingSnapshot->setTitle($snapshotDescriptor->getTitle());

        // Update config
        $config = $existingSnapshot->returnConfig();
        $config->setParameterValues($snapshotDescriptor->getParameterValues());

        // Map indexes to
        $indexes = array_map(fn($index) => new Index($index), $snapshotDescriptor->getIndexes() ?? []);
        $config->setIndexes($indexes);

        $existingSnapshot->setConfig($config);

        try {

            $key = $this->dataProcessorService->saveDataProcessorInstance($existingSnapshot);

            // If run now, trigger snapshot now
            if ($snapshotDescriptor->isRunNow())
                $this->triggerSnapshot($managementKey, $key);

            return ["snapshotKey" => $key];

        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException("The API key used does not have sufficient permissions to manage snapshots.");
        }


    }


    /**
     * Trigger a snapshot for a snapshot key
     *
     * @http PATCH /$managementKey/$snapshotKey
     *
     * @param string $managementKey
     * @param string $snapshotKey
     * @return void
     */
    public function triggerSnapshot($managementKey, $snapshotKey) {

        // Verify snapshot key exists
        $this->verifySnapshotKeyExistsForManagementKey($managementKey, $snapshotKey);

        try {

            // Trigger data processor
            $this->dataProcessorService->triggerDataProcessorInstance($snapshotKey);

        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException("The API key used does not have sufficient permissions to manage snapshots.");
        }

        return ["status" => "success"];
    }


    /**
     * Kill a snapshot for a snapshot key
     *
     * @http PATCH /kill/$managementKey/$snapshotKey
     *
     * @param $managementKey
     * @param $snapshotKey
     * @return array
     */
    public function killSnapshot($managementKey, $snapshotKey) {

        // Verify snapshot key exists
        $this->verifySnapshotKeyExistsForManagementKey($managementKey, $snapshotKey);

        try {

            // Kill data processor
            $this->dataProcessorService->killDataProcessorInstance($snapshotKey);

        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException("The API key used does not have sufficient permissions to manage snapshots.");
        }

        return ["status" => "success"];

    }


    /**
     * Remove a snapshot defined for a given dataset with the passed management key
     *
     * @http DELETE /$managementKey/$snapshotKey
     *
     * @param string $managementKey
     * @param string $snapshotKey
     * @return void
     */
    public function removeSnapshot($managementKey, $snapshotKey) {

        // Verify snapshot key exists
        $this->verifySnapshotKeyExistsForManagementKey($managementKey, $snapshotKey);

        try {
            $this->dataProcessorService->removeDataProcessorInstance($snapshotKey);
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedException("The API key used does not have sufficient permissions to manage snapshots.");
        }

        return ["status" => "success"];
    }


    // Return boolean indicator that snapshot key exists for management key
    private function verifySnapshotKeyExistsForManagementKey($managementKey, $snapshotKey) {
        $allSnapshots = $this->listSnapshotsForManagementKey($managementKey);
        $indexedSnapshots = ObjectArrayUtils::indexArrayOfObjectsByMember("key", $allSnapshots);
        if (!($indexedSnapshots[$snapshotKey] ?? null)) {
            throw new ItemNotFoundException("No snapshot exists for key '$snapshotKey' for data set with management key '$managementKey'");
        } else {
            return $indexedSnapshots[$snapshotKey];
        }
    }


}