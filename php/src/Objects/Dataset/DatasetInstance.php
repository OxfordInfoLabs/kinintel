<?php

namespace Kinintel\Objects\Dataset;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Exception\InvalidTransformationTypeException;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * @table ki_dataset_instance
 * @generate
 */
class DatasetInstance extends ActiveRecord {

    /**
     * The auto generated id for this data set instance
     *
     * @var integer
     */
    private $id;

    /**
     * Information title for this data set
     *
     * @var string
     * @required
     */
    private $title;


    /**
     * The datasource being referenced for this data set instance
     *
     * @var string
     * @required
     */
    private $datasourceInstanceKey;

    /**
     * Array of transformation instances applied in sequence
     * to get to the final data set
     *
     * @var TransformationInstance[]
     * @json
     * @sqlType LONGTEXT
     */
    private $transformationInstances;


    /**
     * DatasetInstance constructor.
     * @param string $title
     * @param $datasourceInstanceKey
     * @param TransformationInstance[] $transformationInstances
     */
    public function __construct($title, $datasourceInstanceKey, $transformationInstances = []) {
        $this->title = $title;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->transformationInstances = $transformationInstances;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
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
     * @return TransformationInstance[]
     */
    public function getTransformationInstances() {
        return $this->transformationInstances;
    }

    /**
     * @param TransformationInstance[] $transformationInstances
     */
    public function setTransformationInstances($transformationInstances) {
        $this->transformationInstances = $transformationInstances;
    }

    /**
     * Implement validate method to perform additional validation as required
     */
    public function validate() {

        $validationErrors = [];

        // Confirm that the datasource instance exists


        /**
         * @var DatasourceService $dataSourceService
         */
        $dataSourceService = Container::instance()->get(DatasourceService::class);
        $instance = $dataSourceService->getDataSourceInstanceByKey($this->datasourceInstanceKey);
        if (!$instance) {
            $validationErrors["datasourceInstanceKey"] = new FieldValidationError("datasourceInstanceKey", "notfound", "Data source with instance key '{$this->datasourceInstanceKey}' does not exist");
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