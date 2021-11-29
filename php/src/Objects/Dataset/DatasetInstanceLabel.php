<?php


namespace Kinintel\Objects\Dataset;

use Kiniauth\Objects\MetaData\ObjectTag;

/**
 * @table ki_dataset_instance
 */
class DatasetInstanceLabel {

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
     * @var ObjectTag[]
     * @oneToMany
     * @childJoinColumns object_id, object_type=KiDatasetInstance
     */
    protected $tags;

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
     * @return ObjectTag[]
     */
    public function getTags() {
        return $this->tags;
    }


}