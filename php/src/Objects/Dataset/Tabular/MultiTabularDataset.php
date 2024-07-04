<?php

namespace Kinintel\Objects\Dataset\Tabular;

class MultiTabularDataset extends TabularDataset {

    /**
     * @param TabularDataset[] $tabularDatasets
     * @param $columns
     * @param $cacheAllRows
     */
    public function __construct(private array $tabularDatasets, $columns = [], $cacheAllRows = true) {
        parent::__construct($columns, $cacheAllRows);
    }

    public function nextRawDataItem() {
        if (!$this->tabularDatasets){
            return false; // Signifies end of stream
        }
        $dataset = $this->tabularDatasets[0];
        // We don't use nextRawDataItem because we want to apply value expressions;
        $entry = $dataset->nextDataItem();
        if ($entry === false) {
            array_shift($this->tabularDatasets);
            return $this->nextRawDataItem();
        } else {
            return $entry;
        }
    }
}