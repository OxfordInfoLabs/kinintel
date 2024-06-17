<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\Query;

use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorAction;
use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorActions;

class QueryCachingDataProcessorConfiguration {

    use DataProcessorActions;

    /**
     * @var int
     */
    private int $sourceQueryId;

    /**
     * @var ?int
     */
    private ?int $cacheExpiryDays;

    /**
     * @var ?int
     */
    private ?int $cacheExpiryHours;

    /**
     * @var string[]
     */
    private array $primaryKeyColumnNames;

    /**
     * @param int $sourceQueryId
     * @param ?int $cacheExpiryDays
     * @param ?int $cacheExpiryHours
     * @param array $primaryKeyColumnNames
     */
    public function __construct(int $sourceQueryId, ?int $cacheExpiryDays = null, ?int $cacheExpiryHours = null, array $primaryKeyColumnNames = []) {
        $this->sourceQueryId = $sourceQueryId;
        $this->cacheExpiryDays = $cacheExpiryDays;
        $this->cacheExpiryHours = $cacheExpiryHours;
        $this->primaryKeyColumnNames = $primaryKeyColumnNames;
    }

    /**
     * @return int
     */
    public function getSourceQueryId(): int {
        return $this->sourceQueryId;
    }

    /**
     * @param int $sourceQueryId
     * @return void
     */
    public function setSourceQueryId(int $sourceQueryId): void {
        $this->sourceQueryId = $sourceQueryId;
    }

    public function getCacheExpiryDays(): ?int {
        return $this->cacheExpiryDays;
    }

    public function setCacheExpiryDays(?int $cacheExpiryDays): void {
        $this->cacheExpiryDays = $cacheExpiryDays;
    }

    public function getCacheExpiryHours(): ?int {
        return $this->cacheExpiryHours;
    }

    public function setCacheExpiryHours(?int $cacheExpiryHours): void {
        $this->cacheExpiryHours = $cacheExpiryHours;
    }

    public function getPrimaryKeyColumnNames(): array {
        return $this->primaryKeyColumnNames;
    }

    public function setPrimaryKeyColumnNames(array $primaryKeyColumnNames): void {
        $this->primaryKeyColumnNames = $primaryKeyColumnNames;
    }

    // Get processor actions
    public function getProcessorActions($dataProcessorInstanceKey) {
        return [
            new DataProcessorAction("Latest", $dataProcessorInstanceKey . "_caching"),
            new DataProcessorAction("Historical Entries", $dataProcessorInstanceKey . "_cache")
        ];
    }
}