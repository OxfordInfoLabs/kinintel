<?php


namespace Kinintel\Objects\Datasource;

use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;
use Kinintel\Exception\ImportKeyAlreadyExistsException;
use Kinintel\Exception\ItemInUseException;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Services\Datasource\DatasourceDAO;
use Monolog\Logger;

class DatasourceInstanceInterceptor extends DefaultORMInterceptor {

    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    /**
     * @var DatasourceDAO
     */
    private $datasourceDAO;


    /**
     * DatasourceInstanceInterceptor constructor.
     *
     * @param DatabaseConnection $databaseConnection
     * @param DatasourceDAO $datasourceDAO
     */
    public function __construct($databaseConnection, $datasourceDAO) {
        $this->databaseConnection = $databaseConnection;
        $this->datasourceDAO = $datasourceDAO;
    }

    /**
     * Check to ensure if an import key is set that there is no overlap
     *
     * @param DatasourceInstance $object
     */
    public function preSave($object) {
        if ($object->getImportKey() && !$this->datasourceDAO->importKeyAvailableForDatasourceInstance($object, $object->getImportKey())) {
            throw new ImportKeyAlreadyExistsException($object->getImportKey());
        }
    }


    /**
     * Ensure on instance save is called on a save
     *
     * @param $object
     */
    public function postSave($object) {

        $datasource = $object->returnDataSource();

        // Call the instance delete if an updatable datasource
        if ($datasource instanceof UpdatableDatasource)
            $datasource->onInstanceSave();

    }


    /**
     * Check to see whether this object is referenced elsewhere
     *
     * @param $object
     */
    public function preDelete($object) {

        // Check for references in datasets before allowing the delete
        $references = $this->databaseConnection->query("SELECT COUNT(*) total FROM ki_dataset_instance WHERE datasource_instance_key = ?", $object->getKey())->fetchAll();

        if ($references[0]["total"]) {
            throw new ItemInUseException($object);
        }

        // Finally check for references encoded as structured data.
        $references = $this->databaseConnection->query("SELECT COUNT(*) total FROM ka_object_structured_data 
                WHERE object_type = ? AND data_type = ? AND primary_key = ?", DatasetInstance::class, "referencedDataSource", $object->getKey())->fetchAll();

        if ($references[0]["total"]) {
            throw new ItemInUseException($object);
        }


    }


    /**
     * Ensure we perform any instance specific clean up on delete of the datasource
     *
     * @param DatasourceInstance $object
     */
    public function postDelete($object) {

        $datasource = $object->returnDataSource();

        // Call the instance delete if an updatable datasource
        if ($datasource instanceof UpdatableDatasource)
            $datasource->onInstanceDelete();
    }


}