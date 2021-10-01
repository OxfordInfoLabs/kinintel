<?php


namespace Kinintel\Traits\Controller\Admin;


use Kinikit\Core\Logging\Logger;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Services\Alert\AlertService;

trait Alert {

    /**
     * @var AlertService
     */
    private $alertService;


    /**
     * Alert constructor.
     *
     * @param AlertService $alertService
     */
    public function __construct($alertService) {
        $this->alertService = $alertService;
    }


    /**
     * @http POST /dashboardDataSetInstance
     * @unsanitise dashboardDataSetInstance
     *
     * @param DashboardDatasetInstance $dashboardDataSetInstance
     * @param integer $alertIndex
     */
    public function processAlertsForDashboardDataset($dashboardDataSetInstance, $alertIndex = null) {
        return $this->alertService->processAlertsForDashboardDatasetInstance($dashboardDataSetInstance, $alertIndex);
    }


}
