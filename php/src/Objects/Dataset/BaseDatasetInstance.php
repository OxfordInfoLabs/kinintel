<?php


namespace Kinintel\Objects\Dataset;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Exception\InvalidTransformationTypeException;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class BaseDatasetInstance extends ActiveRecord {

    /**
     * The datasource being referenced for this data set instance
     *
     * @var string
     */
    protected $datasourceInstanceKey;


    /**
     * Instance id for the referenced data set if using
     *
     * @var integer
     */
    protected $datasetInstanceId;


    /**
     * Array of transformation instances applied in sequence
     * to get to the final data set
     *
     * @var TransformationInstance[]
     * @json
     * @sqlType LONGTEXT
     */
    protected $transformationInstances = [];


    /**
     * Array of parameters defined by this data set.  These
     * augment any parameters provided by the underlying datasource
     * and are most often used to parameterise transformation instances.
     *
     * @var Parameter[]
     * @json
     * @sqlType LONGTEXT
     */
    protected $parameters = [];


    /**
     * Array of parameter values saved with this dataset.
     *
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    protected $parameterValues = [];


    /**
     * @param TransformationInstance[] $transformationInstances
     */
    public function setTransformationInstances($transformationInstances) {
        $this->transformationInstances = $transformationInstances;
    }

    /**
     * @return TransformationInstance[]
     */
    public function getTransformationInstances() {
        return $this->transformationInstances;
    }

    /**
     * @return Parameter[]
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @param Parameter[] $parameters
     */
    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }

    /**
     * @return mixed
     */
    public function getParameterValues() {
        return $this->parameterValues;
    }

    /**
     * @param mixed $parameterValues
     */
    public function setParameterValues($parameterValues) {
        $this->parameterValues = $parameterValues;
    }


    /**
     * @return mixed
     */
    public function getDatasourceInstanceKey() {
        return $this->datasourceInstanceKey;
    }

    /**
     * @param mixed $datasourceInstanceKey
     */
    public function setDatasourceInstanceKey($datasourceInstanceKey) {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
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
     * Implement validate method to perform additional validation as required
     */
    public function validate() {

        $validationErrors = [];

        // Confirm that the datasource instance exists if a key supplied
        if ($this->datasourceInstanceKey) {
            /**
             * @var DatasourceService $dataSourceService
             */
            $dataSourceService = Container::instance()->get(DatasourceService::class);

            try {
                $dataSourceService->getDataSourceInstanceByKey($this->datasourceInstanceKey);
            } catch (ObjectNotFoundException $e) {
                $validationErrors["datasourceInstanceKey"] = new FieldValidationError("datasourceInstanceKey", "notfound", "Data source with instance key '{$this->datasourceInstanceKey}' does not exist");
            }

        }

        // Confirm that the dataset instance exists if an id supplied.
        if ($this->datasetInstanceId) {
            /**
             * @var DatasetService $dataSetService
             */
            $dataSetService = Container::instance()->get(DatasetService::class);

            try {
                $dataSetService->getDataSetInstance($this->datasetInstanceId);
            } catch (ObjectNotFoundException $e) {
                $validationErrors["datasetInstanceId"] = new FieldValidationError("datasetInstanceId", "notfound", "Data set with instance id '{$this->datasetInstanceId}' does not exist");
            }
        }


        // Check that the transformations are valid
        foreach ($this->transformationInstances ?? [] as $index => $transformationInstance) {
            try {
                $transformationInstance->returnTransformation();
            } catch (InvalidTransformationTypeException $e) {
                $validationErrors["transformationInstances"][$index]["type"] = new FieldValidationError("type", "notfound", "Transformation of type '{$transformationInstance->getType()}' does not exist");
            } catch (InvalidTransformationConfigException $e) {
                $validationErrors["transformationInstances"][$index]["config"] = $e->getValidationErrors();
            }
        }

        return $validationErrors;


    }


}