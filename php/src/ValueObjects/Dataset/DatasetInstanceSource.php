<?php


namespace Kinintel\ValueObjects\Dataset;


class DatasetInstanceSource {

    /**
     * @var string
     */
    private $datasourceInstanceKey;


    /**
     * @var string
     */
    private $datasetInstanceId;


    /**
     * @var string
     */
    private $title;


    /**
     * @var string
     */
    private $type;

    /**
     * DatasetSource constructor.
     *
     * @param string $title
     * @param string $datasourceInstanceKey
     * @param string $datasetInstanceId
     * @param string $type
     */
    public function __construct($title, $datasourceInstanceKey = null, $datasetInstanceId = null, $type = null) {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->title = $title;
        $this->type = $type;
    }


    /**
     * @return string
     */
    public function getDatasourceInstanceKey() {
        return $this->datasourceInstanceKey;
    }

    /**
     * @return string
     */
    public function getDatasetInstanceId() {
        return $this->datasetInstanceId;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }


}