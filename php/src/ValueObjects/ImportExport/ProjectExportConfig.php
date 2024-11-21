<?php

namespace Kinintel\ValueObjects\ImportExport;

/**
 * Extended project export config
 *
 */
class ProjectExportConfig extends \Kiniauth\ValueObjects\ImportExport\ProjectExportConfig {

    /**
     * Add alert group ids.
     *
     * @param array $includedNotificationGroupIds
     * @param bool[int] $includedAlertGroupIdUpdateIndicators
     */
    public function __construct(array $includedNotificationGroupIds = [], private array $includedAlertGroupIdUpdateIndicators = []) {
        parent::__construct($includedNotificationGroupIds);
    }

    /**
     * @return bool[int]
     */
    public function getIncludedAlertGroupIdUpdateIndicators(): array {
        return $this->includedAlertGroupIdUpdateIndicators;
    }


}