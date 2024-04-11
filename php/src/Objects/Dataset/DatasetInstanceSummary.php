<?php


namespace Kinintel\Objects\Dataset;


use Kiniauth\Objects\MetaData\CategorySummary;
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
     * Summary for the dashboard
     *
     * @var string
     */
    protected $summary;


    /**
     * Full HTML description for the dashboard
     *
     * @var string
     * @sqlType LONGTEXT
     */
    protected $description;

    /**
     * Array of category summary objects associated with this dashboard
     *
     * @var CategorySummary[]
     */
    protected $categories = [];


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
     * Management key, used when involving this dataset instance in automated workflows.
     *
     * @var string
     */
    protected $managementKey;


    /**
     * DatasetInstance constructor.
     *
     * @param string $title
     * @param string $datasourceInstanceKey
     * @param integer $datasetInstanceId
     * @param TransformationInstance[] $transformationInstances
     * @param array $parameters
     * @param mixed[] $parameterValues
     * @param string $summary
     * @param string $description
     * @param CategorySummary[] $categories
     * @param integer $id
     * @param DatasetInstanceSearchResult $sourceDataSet
     * @param string $managementKey
     */
    public function __construct($title, $datasourceInstanceKey = null, $datasetInstanceId = null, $transformationInstances = [], $parameters = [],
                                $parameterValues = [], $summary = null, $description = null, $categories = [], $id = null, $sourceDataSet = null,
                                $managementKey = null) {
        $this->title = $title;
        $this->summary = $summary;
        $this->description = $description;
        $this->categories = $categories;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->transformationInstances = $transformationInstances;
        $this->parameters = $parameters;
        $this->parameterValues = $parameterValues;
        $this->id = $id;
        $this->sourceDataset = $sourceDataSet;
        $this->managementKey = $managementKey;
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
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
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

    /**
     * @return string
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * @param string $summary
     */
    public function setSummary($summary) {
        $this->summary = $summary;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return CategorySummary[]
     */
    public function getCategories() {
        return $this->categories;
    }

    /**
     * @param CategorySummary[] $categories
     */
    public function setCategories($categories) {
        $this->categories = $categories;
    }

    /**
     * @return string
     */
    public function getManagementKey() {
        return $this->managementKey;
    }

    /**
     * @param string $managementKey
     */
    public function setManagementKey($managementKey) {
        $this->managementKey = $managementKey;
    }


}
