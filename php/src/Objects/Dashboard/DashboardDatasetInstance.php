<?php


namespace Kinintel\Objects\Dashboard;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Dataset\BaseDatasetInstance;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

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
     * @var Alert[]
     * @oneToMany
     * @childJoinColumns dashboard_id,dashboard_dataset_instance_id
     */
    private $alerts;



    /**
     * DashboardDatasetInstance constructor.
     * @param string $instanceId
     * @param int $datasetInstanceId
     * @param string $dataSourceInstanceKey
     * @param TransformationInstance[] $transformationInstances
     * @param Alert[] $alerts
     * @param integer $dashboardId
     */
    public function __construct($instanceId, $datasetInstanceId = null, $dataSourceInstanceKey = null, $transformationInstances = null, $alerts = null, $dashboardId = null) {
        $this->instanceKey = $instanceId;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->datasourceInstanceKey = $dataSourceInstanceKey;
        $this->transformationInstances = $transformationInstances;
        $this->alerts = $alerts;
        $this->dashboardId = $dashboardId;
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
     * @return Alert[]
     */
    public function getAlerts() {
        return $this->alerts;
    }

    /**
     * @param Alert[] $alerts
     */
    public function setAlerts($alerts) {
        $this->alerts = $alerts;
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
