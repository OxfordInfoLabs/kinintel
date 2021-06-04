<?php


namespace Kinintel\Objects\DataProcessor;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Data processor instance class
 */
class DataProcessorInstanceSummary extends ActiveRecord {

    /**
     * Key for the related processor
     *
     * @var string
     * @primaryKey
     */
    protected $key;

    /**
     * Informational title for this data processor.
     *
     * @var string
     * @required
     */
    protected $title;

    /**
     * DataProcessorInstanceSummary constructor.
     *
     * @param string $key
     * @param string $title
     */
    public function __construct($key, $title) {
        $this->key = $key;
        $this->title = $title;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }


}