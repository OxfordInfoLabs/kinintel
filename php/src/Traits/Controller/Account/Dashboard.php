<?php


namespace Kinintel\Traits\Controller\Account;


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
     * Filter dashboards, optionally by title, project key, tags and limited by offset and limit.
     *
     * @http GET /
     *
     * @param string $filterString
     * @param string $categories
     * @param string $projectKey
     * @param string $tags
     * @param int $offset
     * @param int $limit
     *
     * @return DashboardSearchResult[]
     */
    public function filterDashboards($filterString = "", $categories = "", $projectKey = null, $tags = "", $offset = 0, $limit = 10) {
        $tags = $tags ? explode(",", $tags) : [];
        $categories = $categories ? explode(",", $categories) : [];
        return $this->dashboardService->filterDashboards($filterString, $categories, $tags, $projectKey, $offset, $limit);
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
    public function getInUseDashboardCategories($projectKey = null, $tags = "") {
        return $this->dashboardService->getInUseDashboardCategories($tags, $projectKey);
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
        return $this->dashboardService->saveDashboard($dashboardSummary, $projectKey);
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
     * @http DELETE /$id
     * @param $id
     */
    public function removeDashboard($id) {
        $this->dashboardService->removeDashboard($id);
    }


}
