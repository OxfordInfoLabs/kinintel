<?php


namespace Kinintel\Objects\Dataset;


use Kiniauth\Objects\MetaData\ObjectStructuredData;
use Kiniauth\Services\MetaData\MetaDataService;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;
use Kinintel\Exception\ImportKeyAlreadyExistsException;
use Kinintel\Exception\ItemInUseException;
use Kinintel\Exception\ManagementKeyAlreadyExistsException;
use Kinintel\Services\Dataset\DatasetService;

class DatasetInstanceInterceptor extends DefaultORMInterceptor {


    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;


    /**
     * @var MetaDataService
     */
    private $metaDataService;


    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * DatasourceInstanceInterceptor constructor.
     *
     * @param DatabaseConnection $databaseConnection
     * @param MetaDataService $metaDataService
     * @param DatasetService $datasetService
     */
    public function __construct($databaseConnection, $metaDataService, $datasetService) {
        $this->databaseConnection = $databaseConnection;
        $this->metaDataService = $metaDataService;
        $this->datasetService = $datasetService;
    }


    /**
     * Presave method - checks for uniqueness of management keys
     *
     * @param DatasetInstance $object
     * @return void
     */
    public function preSave($object) {
        if ($object->getManagementKey() && !$this->datasetService->managementKeyAvailableForDatasetInstance($object, $object->getManagementKey())) {
            throw new ManagementKeyAlreadyExistsException($object->getManagementKey());
        }
    }


    /**
     * Post save method
     *
     * @param DatasetInstance $object
     */
    public function postSave($object) {

        $associatedDataItems = [];
        foreach ($object->getTransformationInstances() as $transformationInstance) {
            if ($transformationInstance->getType() == "join") {
                $associatedDataItems[] = new ObjectStructuredData(DatasetInstance::class, $object->getId(),
                    $transformationInstance->getConfig()->getJoinedDataSourceInstanceKey() ? "referencedDataSource" : "referencedDataSet",
                    $transformationInstance->getConfig()->getJoinedDataSourceInstanceKey() ?? $transformationInstance->getConfig()->getJoinedDataSetInstanceId(), 1);
            }
        }

        if (sizeof($associatedDataItems))
            $this->metaDataService->replaceStructuredDataItems($associatedDataItems);
        else {
            $this->metaDataService->removeStructuredDataItemsForObjectAndType(DatasetInstance::class, $object->getId(), "referencedDataSource");
            $this->metaDataService->removeStructuredDataItemsForObjectAndType(DatasetInstance::class, $object->getId(), "referencedDataSet");
        }

    }


    /**
     * Override pre-delete to track dependencies
     *
     * @param $object
     */
    public function preDelete($object) {

        // Check for references in child datasets before allowing the delete
        $references = $this->databaseConnection->query("SELECT COUNT(*) total FROM ki_dataset_instance WHERE dataset_instance_id = ? AND id <> ?", $object->getId(), $object->getId())->fetchAll();

        if ($references[0]["total"]) {
            throw new ItemInUseException($object);
        }


        // Check for references in snapshots before allowing the delete
        $references = $this->databaseConnection->query("SELECT COUNT(*) total FROM ki_dataset_instance_snapshot_profile WHERE dataset_instance_id = ?", $object->getId())->fetchAll();

        if ($references[0]["total"]) {
            throw new ItemInUseException($object);
        }

        // Check for references in snapshots before allowing the delete
        $references = $this->databaseConnection->query("SELECT COUNT(*) total FROM ki_feed WHERE dataset_instance_id = ?", $object->getId())->fetchAll();

        if ($references[0]["total"]) {
            throw new ItemInUseException($object);
        }

        // Finally check for references encoded as structured data.
        $references = $this->databaseConnection->query("SELECT COUNT(*) total FROM ka_object_structured_data 
                WHERE object_type = ? AND data_type = ? AND primary_key = ?", DatasetInstance::class, "referencedDataSet", $object->getId())->fetchAll();

        if ($references[0]["total"]) {
            throw new ItemInUseException($object);
        }

    }


}