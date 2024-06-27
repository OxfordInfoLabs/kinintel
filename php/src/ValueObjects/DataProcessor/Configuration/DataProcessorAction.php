<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration;

class DataProcessorAction {

    public function __construct(private string  $title,
                                private ?string $datasourceKey,
                                private ?int    $datasetId = null) {
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getDatasourceKey(): ?string {
        return $this->datasourceKey;
    }

    /**
     * @return int|null
     */
    public function getDatasetId(): ?int {
        return $this->datasetId;
    }


}