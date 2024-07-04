<?php

namespace Kinintel\Objects\Dataset\Tabular;

class MultiTabularDataset extends TabularDataset {

    private int $rowsToRead;

    /**
     * @param TabularDataset[] $tabularDatasets
     * @param $columns
     * @param $cacheAllRows
     */
    public function __construct(
        private array $tabularDatasets,
        $columns = [],
        $cacheAllRows = true,
        int $offset = 0,
        private int $limit = PHP_INT_MAX
    ) {
        // if offset, forward wind to that offset
        $this->rowsToRead = PHP_INT_MAX; // No limit when winding
        if ($offset) {
            for ($i = 0; $i < $offset; $i++) {
                $this->nextRawDataItem();
            }
        }
        $this->rowsToRead = $limit;
        parent::__construct($columns, $cacheAllRows);
    }

    public function nextRawDataItem() {
        if (!$this->tabularDatasets || $this->rowsToRead <= 0){
            return false; // Signifies end of stream
        }
        $dataset = $this->tabularDatasets[0];
        // We don't use nextRawDataItem because we want to apply value expressions;
        $entry = $dataset->nextDataItem();
        if ($entry === false) {
            array_shift($this->tabularDatasets);
            return $this->nextRawDataItem();
        } else {
            $this->rowsToRead -= 1;
            return $entry;
        }
    }
}