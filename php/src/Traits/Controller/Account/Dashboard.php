<?php


namespace Kinintel\Traits\Controller\Account;


use Kinintel\Objects\Dashboard\DashboardSearchResult;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Services\Dashboard\DashboardService;

trait Dashboard {

    /**
     * @var DashboardService
     */
    private $dashboardService;

    /**
     * Dashboard constructor.
     *
     * @param DashboardService $dashboardService
     */
    public function __construct($dashboardService) {
        $this->dashboardService = $dashboardService;
    }


    /**
     * Get a dashboard summary by id
     *
     * @http GET /$id
     *
     * @param $id
     * @return DashboardSummary
     */
    public function getDashboard($id) {
        return $this->dashboardService->getDashboardById($id);
    }


    /**
     * Filter dashboards, optionally by title, project key, tags and limited by offset and limit.
     *
     * @http GET /
     *
     * @param string $filterString
     * @param string $projectKey
     * @param string $tags
     * @param int $offset
     * @param int $limit
     *
     * @return DashboardSearchResult[]
     */
    public function filterDatasetInstances($filterString = "", $projectKey = null, $tags = "", $offset = 0, $limit = 10) {
        $tags = $tags ? explode(",", $tags) : [];
        return $this->dashboardService->filterDashboards($filterString, $tags, $projectKey, $offset, $limit);
    }


    /**
     * Save a dashboard object
     *
     * @http POST
     * @unsanitise dashboardSummary
     *
     * @param DashboardSummary $dashboardSummary
     * @param string $projectKey
     */
    public function saveDashboard($dashboardSummary, $projectKey = null) {
        $this->dashboardService->saveDashboard($dashboardSummary, $projectKey);
    }


    /**
     * Remove a dashboard by id
     *
     * @param $id
     */
    public function removeDashboard($id) {
        $this->dashboardService->removeDashboard($id);
    }


}
