<?php

namespace Kinintel\Objects\Dashboard;

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
     * Dashboard constructor.
     *
     * @param DashboardSummary $dashboardSummary
     * @param integer $accountId
     * @param integer $projectNumber
     */
    public function __construct($dashboardSummary = null, $accountId = null, $projectNumber = null) {
        if ($dashboardSummary instanceof DashboardSummary)
            parent::__construct($dashboardSummary->getTitle(), $dashboardSummary->getDatasetInstances(), $dashboardSummary->getDisplaySettings(), $dashboardSummary->getId());
        $this->accountId = $accountId;
        $this->projectNumber = $projectNumber;
    }

    /**
     * Return a dashboard summary
     */
    public function returnSummary() {
        return new DashboardSummary($this->title, $this->datasetInstances, $this->displaySettings, $this->id);
    }

}