<?php


namespace Kinintel\Traits\Controller\Admin;


use Kiniauth\Objects\MetaData\CategorySummary;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
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
        try {
            return $this->dashboardService->getDashboardById($id);
        } catch (ObjectNotFoundException $e) {
            return new DashboardSummary("");
        }
    }


    /**
     * Copy a dashboard from it's id.
     *
     * @http GET /copy/$id
     *
     * @param $id
     * @return DashboardSummary
     */
    public function copyDashboard($id) {
        return $this->dashboardService->copyDashboard($id);
    }


    /**
     * Extends a dashboard by id
     *
     * @http GET /extend/$id
     *
     * @param $id
     * @return DashboardSummary
     */
    public function extendDashboard($id) {
        return $this->dashboardService->extendDashboard($id);
    }


    /**
     * Filter dashboards, optionally by title, project key, tags and limited by offset and limit.
     *
     * @http GET /
     *
     * @param string $filterString
     * @param string $categories
     * @param string $accountId
     * @param int $offset
     * @param int $limit
     *
     * @return DashboardSearchResult[]
     */
    public function filterDashboards($filterString = "", $categories = "", $accountId = 0, $offset = 0, $limit = 10) {
        $categories = $categories ? explode(",", $categories) : [];
        return $this->dashboardService->filterDashboards($filterString, $categories, [], true, null, $limit, $offset, is_numeric($accountId) ? $accountId : null);
    }


    /**
     * Filter in use dashboard categories optionally for a project and tags
     *
     * @http GET /inUseCategories
     *
     * @param string $projectKey
     * @param string $tags
     *
     * @return CategorySummary[]
     */
    public function getInUseDashboardCategories($projectKey = null, $tags = "", $accountId = 0) {
        return $this->dashboardService->getInUseDashboardCategories($tags, $projectKey, $accountId);
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
    public function saveDashboard($dashboardSummary, $projectKey = null, $accountId = 0) {
        return $this->dashboardService->saveDashboard($dashboardSummary, $projectKey, is_numeric($accountId) ? $accountId : null);
    }


    /**
     * Update meta data for a dashboard
     *
     * @http PATCH
     * @unsanitise dashboardSearchResult
     *
     * @param DashboardSearchResult $dashboardSearchResult
     */
    public function updateDashboardMetaData($dashboardSearchResult) {
        $this->dashboardService->updateDashboardMetaData($dashboardSearchResult);
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
