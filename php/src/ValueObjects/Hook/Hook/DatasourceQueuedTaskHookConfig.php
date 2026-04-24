<?php

namespace Kinintel\ValueObjects\Hook\Hook;

use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Hook\DatasourceHookConfig;

class DatasourceQueuedTaskHookConfig implements DatasourceHookConfig {

    /**
     * @param Field[] $fields
     */
    public function __construct(
        public array $fields = [],
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

}