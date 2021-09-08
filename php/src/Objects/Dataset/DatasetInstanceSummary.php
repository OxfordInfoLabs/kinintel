<?php


namespace Kinintel\Objects\Dataset;


use Kiniauth\Objects\MetaData\TagSummary;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * @readOnly
 */
class DatasetInstanceSummary extends BaseDatasetInstance {


    /**
     * The auto generated id for this data set instance
     *
     * @var integer
     */
    protected $id;


    /**
     * Information title for this data set
     *
     * @var string
     * @required
     */
    protected $title;

    /**
     * Redeclared instance key with required validation
     *
     * @var string
     * @required
     */
    protected $datasourceInstanceKey;


    /**
     * Array of tag keys associated with this instance summary if required
     *
     * @var TagSummary[]
     */
    protected $tags = [];

    /**
     * DatasetInstance constructor.
     * @param string $title
     * @param $datasourceInstanceKey
     * @param TransformationInstance[] $transformationInstances
     * @param array $parameters
     * @param mixed[] $parameterValues
     * @param integer $id
     */
    public function __construct($title, $datasourceInstanceKey, $transformationInstances = [], $parameters = [],
                                $parameterValues = [], $id = null) {
        $this->title = $title;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->transformationInstances = $transformationInstances;
        $this->parameters = $parameters;
        $this->parameterValues = $parameterValues;
        $this->id = $id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return TagSummary[]
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @param TagSummary[] $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }


}