<?php


namespace Kinintel\Objects\Dataset;


use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;
use Kinintel\Services\Datasource\DatasourceService;


/**
 * Intercept requests for the snapshot profile
 *
 * Class DatasetInstanceSnapshotProfileInterceptor
 * @package Kinintel\Objects\Dataset
 */
class DatasetInstanceSnapshotProfileInterceptor extends DefaultORMInterceptor {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * DatasetInstanceSnapshotProfileInterceptor constructor.
     *
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;
    }

    /**
     * Clean up after a snapshot removal to ensure we throw away the custom datasources.
     *
     * @param DatasetInstanceSnapshotProfile $object
     */
    public function postDelete($object) {

        // Get the snapshot config and look up the identifier
        $identifier = $object->getDataProcessorInstance()->getKey();


        // Now delete the 3 potential datasources
        try {
            $this->datasourceService->removeDatasourceInstance($identifier);
        } catch (ObjectNotFoundException $e) {
            // No probs
        }

        try {
            $this->datasourceService->removeDatasourceInstance($identifier . "_latest");
        } catch (ObjectNotFoundException $e) {
            // No probs
        }

        try {
            $this->datasourceService->removeDatasourceInstance($identifier . "_pending");
        } catch (ObjectNotFoundException $e) {
            // No probs
        }


    }


}