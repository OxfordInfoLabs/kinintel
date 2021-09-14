<?php

namespace Kinintel\Objects\Dashboard;

use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Dashboard - encodes one or more dataset instances along with display configuration
 * and global transformations to be applied to one or more datasets for e.g. global filtering etc.
 *
 * Class Dashboard
 * @table ki_dashboard
 * @generate
 */
class Dashboard extends DashboardSummary {

    // Use the account project trait
    use AccountProject;


    /**
     * @var ObjectTag[]
     * @oneToMany
     * @childJoinColumns object_id, object_type=KiDashboard
     */
    protected $tags = [];

    /**
     * Dashboard constructor.
     *
     * @param DashboardSummary $dashboardSummary
     * @param integer $accountId
     * @param string $projectKey
     */
    public function __construct($dashboardSummary = null, $accountId = null, $projectKey = null) {
        if ($dashboardSummary instanceof DashboardSummary)
            parent::__construct($dashboardSummary->getTitle(), $dashboardSummary->getDatasetInstances(), $dashboardSummary->getDisplaySettings(), $dashboardSummary->getLayoutSettings(),
                $dashboardSummary->isAlertsEnabled(), $dashboardSummary->getId());
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
    }


    /**
     * @return ObjectTag[]
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @param ObjectTag[] $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }

    /**
     * Return a dashboard summary
     */
    public function returnSummary($returnCopy = false) {
        $dashboardSummary = new DashboardSummary($this->title, $this->datasetInstances, $this->displaySettings, $this->layoutSettings,
            $this->alertsEnabled,
            $returnCopy ? null : $this->id);

        // If returning a copy, nullify alert data too
        if ($returnCopy) {
            foreach ($dashboardSummary->getDatasetInstances() ?? [] as $instance) {
                foreach ($instance->getAlerts() as $alert) {
                    $alert->setAlertGroupId(null);
                    $alert->setId(null);
                }
            }
        }

        return $dashboardSummary;

    }

}
