<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\VectorDataset;

class VectorDatasetProcessorConfiguration {

    /**
     * @var int|null
     */
    private ?int $datasetInstanceId;

    /**
     * @var string|null
     */
    private ?string $datasourceInstanceKey;

    /**
     * @var string
     * @required
     */
    private string $vectorDatasourceIdentifier;

    /**
     * @var string[]
     */
    private array $identifierColumnNames;

    /**
     * @var string|null
     * @required
     */
    private ?string $contentColumnName;

    /**
     * @var int|null
     */
    private ?int $readChunkSize;

    /**
     * @param ?int $datasetInstanceId
     * @param ?string $datasourceInstanceKey
     * @param string $vectorDatasourceIdentifier
     * @param string[] $identifierColumnNames
     * @param ?string $contentColumnName
     * @param int|null $readChunkSize
     */
    public function __construct(int $datasetInstanceId = null, string $datasourceInstanceKey = null, string $vectorDatasourceIdentifier = "", array $identifierColumnNames = [], string $contentColumnName = null, ?int $readChunkSize = null) {
        $this->datasetInstanceId = $datasetInstanceId;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->vectorDatasourceIdentifier = $vectorDatasourceIdentifier;
        $this->identifierColumnNames = $identifierColumnNames;
        $this->contentColumnName = $contentColumnName;
        $this->readChunkSize = $readChunkSize;
    }


    public function getDatasetInstanceId(): ?int {
        return $this->datasetInstanceId;
    }

    public function setDatasetInstanceId(?int $datasetInstanceId): void {
        $this->datasetInstanceId = $datasetInstanceId;
    }

    public function getDatasourceInstanceKey(): ?string {
        return $this->datasourceInstanceKey;
    }

    public function setDatasourceInstanceKey(?string $datasourceInstanceKey): void {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
    }

    public function getVectorDatasourceIdentifier(): string {
        return $this->vectorDatasourceIdentifier;
    }

    public function setVectorDatasourceIdentifier(string $vectorDatasourceIdentifier): void {
        $this->vectorDatasourceIdentifier = $vectorDatasourceIdentifier;
    }

    public function getIdentifierColumnNames(): array {
        return $this->identifierColumnNames;
    }

    public function setIdentifierColumnNames(array $identifierColumnNames): void {
        $this->identifierColumnNames = $identifierColumnNames;
    }

    public function getContentColumnName(): ?string {
        return $this->contentColumnName;
    }

    public function setContentColumnName(?string $contentColumnName): void {
        $this->contentColumnName = $contentColumnName;
    }

    public function getReadChunkSize(): ?int {
        return $this->readChunkSize;
    }

    public function setReadChunkSize(?int $readChunkSize): void {
        $this->readChunkSize = $readChunkSize;
    }

}