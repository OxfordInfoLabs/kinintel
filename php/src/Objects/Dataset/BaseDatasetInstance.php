<?php


namespace Kinintel\Objects\Dataset;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Exception\InvalidTransformationTypeException;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class BaseDatasetInstance extends ActiveRecord {

    /**
     * The datasource being referenced for this data set instance
     *
     * @var string
     */
    protected $datasourceInstanceKey;
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