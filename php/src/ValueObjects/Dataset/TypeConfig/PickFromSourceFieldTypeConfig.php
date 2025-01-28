<?php

namespace Kinintel\ValueObjects\Dataset\TypeConfig;

/**
 * Pick one field type config
 */
class PickFromSourceFieldTypeConfig implements FieldTypeConfig {

    /**
     * @param string $labelFieldName
     * @param string $valueFieldName
     * @param int|null $datasetInstanceId
     * @param string|null $datasourceInstanceKey
     */
    public function __construct(private string $labelFieldName, private string $valueFieldName,
                                private ?int   $datasetInstanceId = null, private ?string $datasourceInstanceKey = null) {

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
    public function getDatasetInstanceId(): ?int {
        return $this->datasetInstanceId;
    }

    /**
     * @param int|null $datasetInstanceId
     */
    public function setDatasetInstanceId(?int $datasetInstanceId): void {
        $this->datasetInstanceId = $datasetInstanceId;
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