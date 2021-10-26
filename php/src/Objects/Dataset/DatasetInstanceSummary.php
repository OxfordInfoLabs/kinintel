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
     * Key for datasource this dataset builds upon if not based on a dataset
     *
     * @var string
     * @requiredEither datasetInstanceId
     */
    protected $datasourceInstanceKey;


    /**
     * Parent dataset instance which this dataset builds upon if not built from a data source directly.
     *
     * @var string
     */
    protected $datasetInstanceId;


    /**
     * Array of tag keys associated with this instance summary if required
     *
     * @var TagSummary[]
     */
    protected $tags = [];


    /**
     * DatasetInstance constructor.
     * @param string $title
     * @param string $datasourceInstanceKey
     * @param string $datasetInstanceId
     * @param TransformationInstance[] $transformationInstances
     * @param array $parameters
     * @param mixed[] $parameterValues
     * @param integer $id
     * @param DatasetInstanceSnapshotProfileSummary $snapshotProfiles
     */
    public function __construct($title, $datasourceInstanceKey = null, $datasetInstanceId = null, $transformationInstances = [], $parameters = [],
                                $parameterValues = [], $id = null) {
        $this->title = $title;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->transformationInstances = $transformationInstances;
        $this->parameters = $parameters;
        $this->parameterValues = $parameterValues;
        $this->id = $id;
        $this->datasetInstanceId = $datasetInstanceId;
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
