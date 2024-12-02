<?php

namespace Kinintel\ValueObjects\ImportExport\ExportConfig;

use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;

class DatasourceExportConfig extends ObjectInclusionExportConfig {

    public function __construct(bool $included, private bool $includeData) {
        parent::__construct($included);
    }

    /**
     * @return bool
     */
    public function isIncludeData(): bool {
        return $this->includeData;
    }


}