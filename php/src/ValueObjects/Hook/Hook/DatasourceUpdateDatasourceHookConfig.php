<?php

namespace Kinintel\ValueObjects\Hook\Hook;

use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Hook\DatasourceHookConfig;

class DatasourceUpdateDatasourceHookConfig implements DatasourceHookConfig {

    /**
     * @param string $targetDatasourceKey
     * @param Field[] $fields
     */
    public function __construct(public string $targetDatasourceKey, public array $fields = []) {
    }

    /**
     * @return string
     */
    public function getTargetDatasourceKey(): string {
        return $this->targetDatasourceKey;
    }

    /**
     * @param string $targetDatasourceKey
     */
    public function setTargetDatasourceKey(string $targetDatasourceKey): void {
        $this->targetDatasourceKey = $targetDatasourceKey;
    }

    /**
     * @return array
     */
    public function getFields(): array {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields): void {
        $this->fields = $fields;
    }



}