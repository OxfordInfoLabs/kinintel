<?php

namespace Kinintel\ValueObjects\Dataset\TypeConfig;

/**
 * Pick one field type config
 */
class PickFromSourceFieldTypeConfig implements FieldTypeConfig {

    /**
     * @param string $labelFieldName
     * @param string $valueFieldName
     * @param int|null $datasetId
     * @param string|null $datasourceInstanceKey
     */
    public function __construct(private string $labelFieldName, private string $valueFieldName,
                                private ?int   $datasetId = null, private ?string $datasourceInstanceKey = null) {

    }

    /**
     * @return string
     */
    public function getLabelFieldName(): string {
        return $this->labelFieldName;
    }

    /**
     * @param string $labelFieldName
     */
    public function setLabelFieldName(string $labelFieldName): void {
        $this->labelFieldName = $labelFieldName;
    }

    /**
     * @return string
     */
    public function getValueFieldName(): string {
        return $this->valueFieldName;
    }

    /**
     * @param string $valueFieldName
     */
    public function setValueFieldName(string $valueFieldName): void {
        $this->valueFieldName = $valueFieldName;
    }

    /**
     * @return int|null
     */
    public function getDatasetId(): ?int {
        return $this->datasetId;
    }

    /**
     * @param int|null $datasetId
     */
    public function setDatasetId(?int $datasetId): void {
        $this->datasetId = $datasetId;
    }

    /**
     * @return string|null
     */
    public function getDatasourceInstanceKey(): ?string {
        return $this->datasourceInstanceKey;
    }

    /**
     * @param string|null $datasourceInstanceKey
     */
    public function setDatasourceInstanceKey(?string $datasourceInstanceKey): void {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
    }

    
}