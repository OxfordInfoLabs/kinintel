<?php

namespace Kinintel\ValueObjects\Datasource\Update;

class DatasourceUpdateResult {

    /**
     * Datasource update result
     *
     * @param int $adds
     * @param int $updates
     * @param int $replaces
     * @param int $deletes
     * @param int $rejected
     * @param DatasourceUpdateResultItemValidationErrors[] $validationErrors
     */
    public function __construct(private int $adds = 0, private int $updates = 0, private int $replaces = 0,
                                private int $deletes = 0, private int $rejected = 0, private array $validationErrors = []) {

    }

    /**
     * @return int
     */
    public function getAdds(): int {
        return $this->adds;
    }

    /**
     * @return int
     */
    public function getUpdates(): int {
        return $this->updates;
    }

    /**
     * @return int
     */
    public function getReplaces(): int {
        return $this->replaces;
    }

    /**
     * @return int
     */
    public function getDeletes(): int {
        return $this->deletes;
    }

    /**
     * @return int
     */
    public function getRejected(): int {
        return $this->rejected;
    }

    /**
     * @return DatasourceUpdateResultItemValidationErrors[]
     */
    public function getValidationErrors(): array {
        return $this->validationErrors;
    }


    /**
     * Combine this datasource update result with another one.
     *
     * @param DatasourceUpdateResult $otherDatasourceUpdateResult
     */
    public function combine($otherDatasourceUpdateResult) {

        // Ensure we are upgraded to associative validation errors by type
        if (isset($this->validationErrors[0])) {
            $this->validationErrors = [$this->getChangeMode() => $this->validationErrors];
        }

        $otherValidationErrors = isset($otherDatasourceUpdateResult->validationErrors[0]) ?
            [$otherDatasourceUpdateResult->getChangeMode() => $otherDatasourceUpdateResult->validationErrors] : (
                $otherDatasourceUpdateResult->validationErrors ?? []
            );

        $this->validationErrors = array_merge($this->validationErrors, $otherValidationErrors);

        $this->adds += $otherDatasourceUpdateResult->getAdds();
        $this->updates += $otherDatasourceUpdateResult->getUpdates();
        $this->replaces += $otherDatasourceUpdateResult->getReplaces();
        $this->deletes += $otherDatasourceUpdateResult->getDeletes();
        $this->rejected += $otherDatasourceUpdateResult->getRejected();

    }

    private function getChangeMode() {
        return $this->adds ? "adds" : ($this->updates ? "updates" : ($this->replaces ? "replaces" : "deletes"));
    }


}