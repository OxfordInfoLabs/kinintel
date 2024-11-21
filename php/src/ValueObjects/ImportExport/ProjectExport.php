<?php

namespace Kinintel\ValueObjects\ImportExport;


use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kinintel\Objects\Alert\AlertGroupSummary;

class ProjectExport extends \Kiniauth\ValueObjects\ImportExport\ProjectExport {

    /**
     * @param ProjectExportConfig $exportConfig
     * @param NotificationGroup[] $notificationGroups
     * @param AlertGroupSummary[] $alertGroups
     */
    public function __construct(private ProjectExportConfig $exportConfig, array $notificationGroups,
                                private array       $alertGroups) {
        parent::__construct($notificationGroups);
    }

    /**
     * @return  AlertGroupSummary[]
     */
    public function getAlertGroups(): array {
        return $this->alertGroups;
    }

    /**
     * @return ProjectExportConfig
     */
    public function getExportConfig(): ProjectExportConfig {
        return $this->exportConfig;
    }



}