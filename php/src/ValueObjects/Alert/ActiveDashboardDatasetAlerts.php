<?php


namespace Kinintel\ValueObjects\Alert;


use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;

class ActiveDashboardDatasetAlerts {

    /**
     * @var DashboardDatasetInstance
     */
    private $dashboardDatasetInstance;

    /**
     * @var Alert[]
     */
    private $alerts;

    /**
     * ActiveDashboardDatasetAlerts constructor.
     * @param DashboardDatasetInstance $dashboardDatasetInstance
     * @param Alert[] $alerts
     */
    public function __construct(DashboardDatasetInstance $dashboardDatasetInstance, array $alerts) {
        $this->dashboardDatasetInstance = $dashboardDatasetInstance;
        $this->alerts = $alerts;
    }

    /**
     * @return DashboardDatasetInstance
     */
    public function getDashboardDatasetInstance() {
        return $this->dashboardDatasetInstance;
    }

    /**
     * @return Alert[]
     */
    public function getAlerts() {
        return $this->alerts;
    }


}