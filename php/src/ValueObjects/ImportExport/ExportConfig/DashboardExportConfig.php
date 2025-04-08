<?php

namespace Kinintel\ValueObjects\ImportExport\ExportConfig;

use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;

class DashboardExportConfig extends ObjectInclusionExportConfig {

    public function __construct(bool         $included = true, private bool $includeAlerts = true,
                                private bool $updateAlertTemplates = true) {
        parent::__construct($included);
    }

    /**
     * @return bool
     */
    public function isIncludeAlerts(): bool {
        return $this->includeAlerts;
    }

    /**
     * @return bool
     */
    public function isUpdateAlertTemplates(): bool {
        return $this->updateAlertTemplates;
    }


}