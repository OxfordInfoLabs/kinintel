<?php

namespace Kinintel\Objects\Dataset;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Exception\InvalidTransformationConfigException;
use Kinintel\Exception\InvalidTransformationTypeException;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * @table ki_dataset_instance
 * @generate
 */
class DatasetInstance extends BaseDatasetInstance {

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
     * Redeclared instance key with required validation
     *
     * @var string
     * @required
     */
    protected $datasourceInstanceKey;


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


}