<?php


namespace Kinintel\Objects\Dashboard;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Dataset\BaseDatasetInstance;
use Kinintel\Services\Dataset\DatasetService;

/**
 * Class DashboardDatasetInstance
 *
 * @table ki_dashboard_dataset_instance
 * @generate
 */
class DashboardDatasetInstance extends BaseDatasetInstance {

    /**
     * @var integer
     * @primaryKey
     */
    private $dashboardId;

    /**
     * Unique instance key - generated externally (usually by client)
     *
     * @var string
     * @primaryKey
     */
    private $instanceKey;


    /**
     * Instance id for the referenced data set if using
     *
     * @var integer
     */
    private $datasetInstanceId;

    /**
     * DashboardDatasetInstance constructor.
     * @param string $instanceId
     * @param int $datasetInstanceId
     */
    public function __construct($instanceId, $datasetInstanceId = null, $dataSourceInstanceKey = null, $transformationInstances = null) {
        $this->instanceKey = $instanceId;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->datasourceInstanceKey = $dataSourceInstanceKey;
        $this->transformationInstances = $transformationInstances;
    }


    /**
     * @return int
     */
    public function getDashboardId() {
        return $this->dashboardId;
    }

    /**
     * @param int $dashboardId
     */
    public function setDashboardId($dashboardId) {
        $this->dashboardId = $dashboardId;
    }

    /**
     * @return string
     */
    public function getInstanceKey() {
        return $this->instanceKey;
    }

    /**
     * @param string $instanceKey
     */
    public function setInstanceKey($instanceKey) {
        $this->instanceKey = $instanceKey;
    }

    /**
     * @return int
     */
    public function getDatasetInstanceId() {
        return $this->datasetInstanceId;
    }

    /**
     * @param int $datasetInstanceId
     */
    public function setDatasetInstanceId($datasetInstanceId) {
        $this->datasetInstanceId = $datasetInstanceId;
    }

    /**
     * Override validate to also validate the dataset instance id if supplied
     *
     * @return array
     */
    public function validate() {
        $validationErrors = parent::validate();

        // If data set instance id, validate this
        if ($this->datasetInstanceId) {

            /**
             * @var DatasetService $datasetService
             */
            $datasetService = Container::instance()->get(DatasetService::class);

            try {
                $datasetService->getDataSetInstance($this->datasetInstanceId);
            } catch (ObjectNotFoundException $e) {
                $validationErrors["datasetInstanceId"] = new FieldValidationError("datasetInstanceId", "notfound", "The dataset with instance id {$this->datasetInstanceId} cannot be found");
            }
        }

        return $validationErrors;
    }


}