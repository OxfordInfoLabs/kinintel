<?php

namespace Kinintel\ValueObjects\Hook\Hook;

use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Hook\DatasourceHookConfig;

class DatasourceScheduledTaskHookConfig implements DatasourceHookConfig {

    /**
     * @param Field[] $fields
     */
    public function __construct(
        public array $fields = [],
        public array $filters = []
    ) {
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

    /**
     * @return array
     */
    public function getFilters(): array {
        return $this->filters;
    }

    /**
     * @param array $filters
     */
    public function setFilters(array $filters): void {
        $this->filters = $filters;
    }

}