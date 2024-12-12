<?php

namespace Kinintel\ValueObjects\ImportExport\ExportConfig;

use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;

class ObjectUpdateSelectionExportConfig extends ObjectInclusionExportConfig {

    public function __construct(bool $included, private bool $update) {
        parent::__construct($included);
    }

    /**
     * @return bool
     */
    public function isUpdate(): bool {
        return $this->update;
    }


}