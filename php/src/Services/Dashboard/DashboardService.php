<?php

namespace Kinintel\Services\Dashboard;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSearchResult;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\ValueObjects\Alert\ActiveDashboardDatasetAlerts;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * Main entry point for access to dashboard related functions
 *
 * Class DashboardService
 */
class DashboardService {

    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * @var MetaDataService
     */
    private $metaDataService;


    /**
     * @var SecurityService
     */
    private $securityService;

    /**
     * DashboardService constructor.
     *
     * @param DatasetService $datasetService
     * @param MetaDataService $metaDataService
     * @param SecurityService $securityService
     */
    public function __construct($datasetService, $metaDataService, $securityService) {
        $this->datasetService = $datasetService;
        $this->metaDataService = $metaDataService;
        $this->securityService = $securityService;
    }


    /**
     * Get a dashboard by id
     *
     * @param $id
     * @return DashboardSummary
     */
    public function getDashboardById($id) {
        return $this->getRecursiveDashboardById($id)->returnSummary();
    }


    /**
     * Get a full dashboard by id without summarisation for internal use
     *
     * @param $id
     */
    public function getFullDashboard($id) {
        return Dashboard::fetch($id);
    }


    /**
     * Get a copy of a dashboard.  No link is retained with the
     * original dashboard being copied.
     *
     * @param $id
     */
    public function copyDashboard($id) {
        return Dashboard::fetch($id)->returnSummary(true);
    }


    /**
     * Extend a dashboard.  This will create a link with the original dashboard
     * using the parent dashboard id.
     *
     * @param $id
     */
    public function extendDashboard($parentDashboardId) {

        $parentDashboard = Dashboard::fetch($parentDashboardId);

        $newDashboard = new Dashboard();
        $newDashboard->setParentDashboardId($parentDashboardId);
        $newDashboard->setTitle($parentDashboard->getTitle() . " Extended");
        $this->mergeParentDashboard($newDashboard, $parentDashboardId);

        return $newDashboard->returnSummary();
    }


    /**
     * Get dashboard by title optionally limited to account and project.
     *
     * @param $title
     * @param null $projectKey
     * @param string $accountId
     */
    public function getDashboardByTitle($title, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // If account id or project key, form clause
        $clauses = ["title = ?"];
        $parameters = [$title];
        if ($accountId || $projectKey) {
            $clauses[] = "accountId = ?";
            $parameters[] = $accountId;

            if ($projectKey) {
                $clauses[] = "projectKey = ?";
                $parameters[] = $projectKey;
            }
        } else {
            $clauses[] = "accountId IS NULL";
        }


        $matches = Dashboard::filter("WHERE " . implode(" AND ", $clauses), $parameters);
        if (sizeof($matches) > 0) {
            return $matches[0]->returnSummary();
        } else {
            throw new ObjectNotFoundException(Dashboard::class, $title);
        }

    }





    /**
     * Get all dashboards as summaries
     *
     * @return array
     */
    public function getAllDashboards($projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $clause = "WHERE account_id = ?";
        $params = [$accountId];
        if ($projectKey) {
            $clause .= " AND project_key =?";
            $params[] = $projectKey;
        }
        $clause .= " ORDER BY title";

        return array_map(function ($dashboard) {
            return $dashboard->returnSummary();
        }, Dashboard::filter($clause, $params));
    }


    /**
     * Filter dashboards optionally by title, tags, project key and limiting as required
     *
     * @param string $filterString
     * @param array $categories
     * @param array $tags
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param string $accountId
     *
     * @return DashboardSearchResult[]
     */
    public function filterDashboards($filterString = "", $categories = [], $tags = [], $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $params = [];
        if ($accountId === null) {
            $query = "WHERE accountId IS NULL";
        } else {
            $query = "WHERE accountId = ?";
            $params[] = $accountId;
        }

        if ($filterString) {
            $query .= " AND title LIKE ?";
            $params[] = "%$filterString%";
        }

        if ($projectKey) {
            $query .= " AND project_key = ?";
            $params[] = $projectKey;
        }

        if ($tags && sizeof($tags) > 0) {
            if ($tags[0] == "NONE") {
                $query .= " AND tags.tag_key IS NULL";
            } else {
                $query .= " AND tags.tag_key IN (" . str_repeat("?", sizeof($tags)) . ")";
                $params = array_merge($params, $tags);
            }
        }

        if ($categories && sizeof($categories) > 0) {
            $query .= " AND categories.category_key IN (?" . str_repeat(",?", sizeof($categories) - 1) . ")";
            $params = array_merge($params, $categories);
        }


        $query .= " ORDER BY title LIMIT $limit OFFSET $offset";

        // Return a summary array
        return array_map(function ($instance) {
            $summary = $instance->returnSummary();
            return new DashboardSearchResult($instance->getId(), $instance->getTitle(), $instance->getSummary(), $instance->getDescription(), $summary->getCategories(), $summary->getParentDashboardId());
        },
            Dashboard::filter($query, $params));


    }

    /**
     * Get in use dashboard categories for the supplied account and project and optionally limited to tags.
     *
     * @param string[] $tags
     * @param string $projectKey
     * @param string $accountId
     */
    public function getInUseDashboardCategories($tags = [], $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $params = [];
        if ($accountId === null) {
            $query = "WHERE accountId IS NULL";
        } else {
            $query = "WHERE accountId = ?";
            $params[] = $accountId;
        }

        if ($projectKey) {
            $query .= " AND project_key = ?";
            $params[] = $projectKey;
        }

        if ($tags && sizeof($tags) > 0) {
            $query .= " AND tags.tag_key IN (" . str_repeat("?", sizeof($tags)) . ")";
            $params = array_merge($params, $tags);
        }

        $categoryKeys = Dashboard::values("DISTINCT(categories.category_key)", $query, $params);

        return $this->metaDataService->getMultipleCategoriesByKey($categoryKeys, $projectKey, $accountId);
    }


    /**
     * Save a dashboard
     *
     * @param DashboardSummary $dashboard
     */
    public function saveDashboard($dashboardSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $dashboard = new Dashboard($dashboardSummary, $accountId, $projectKey);

        // Process tags
        if (sizeof($dashboardSummary->getTags())) {
            $tags = $this->metaDataService->getObjectTagsFromSummaries($dashboardSummary->getTags(), $accountId, $projectKey);
            $dashboard->setTags($tags);
        }

        // Process categories
        if (sizeof($dashboardSummary->getCategories())) {
            $categories = $this->metaDataService->getObjectCategoriesFromSummaries($dashboardSummary->getCategories(), $accountId, $projectKey);
            $dashboard->setCategories($categories);
        }

        if ($dashboard->getParentDashboardId()) {
            $this->removeParentData($dashboard);
        }

        $dashboard->save();
        return $dashboard->getId();
    }


    /**
     * Update dashboard meta data
     *
     * @param DashboardSearchResult $dashboardSearchResult
     */
    public function updateDashboardMetaData($dashboardSearchResult) {

        $dashboard = Dashboard::fetch($dashboardSearchResult->getId());
        $dashboard->setTitle($dashboardSearchResult->getTitle());
        $dashboard->setSummary($dashboardSearchResult->getSummary());
        $dashboard->setDescription($dashboardSearchResult->getDescription());
        $dashboard->setCategories($this->metaDataService->getObjectCategoriesFromSummaries($dashboardSearchResult->getCategories(), $dashboard->getAccountId(), $dashboard->getProjectKey()));
        $dashboard->save();
    }

    /**
     * Remove a dashboard by id
     *
     * @param $dashboardId
     */
    public function removeDashboard($dashboardId) {
        $dashboard = Dashboard::fetch($dashboardId);
        $dashboard->remove();
    }


    /**
     * Get the evaluated data set for a dashboard and data set instance referenced by key
     *
     * @param integer $dashboardId
     * @param string $datasetInstanceKey
     * @param TransformationInstance[] $additionalTransformations
     */
    public function getEvaluatedDataSetForDashboardDataSetInstance($dashboardId, $datasetInstanceKey, $additionalTransformations = []) {

        /**
         * @var DashboardDatasetInstance $dashboardDatasetInstance
         */
        $dashboardDatasetInstance = DashboardDatasetInstance::fetch([$dashboardId, $datasetInstanceKey]);

        // If a dataset instance id in use, return using appropriate call
        if ($dashboardDatasetInstance->getDatasetInstanceId()) {
            return $this->datasetService->getEvaluatedDataSetForDataSetInstanceById($dashboardDatasetInstance->getDatasetInstanceId(), $additionalTransformations);
        } else {
            return $this->datasetService->getEvaluatedDataSetForDataSetInstance($dashboardDatasetInstance, $additionalTransformations);
        }

    }

    /**
     * Get evaluated data set for a dashboard data set object
     *
     * @param DashboardDatasetInstance $dashboardDataSet
     * @param TransformationInstance[] $additionalTransformation
     */
    public function getEvaluatedDataSetForDashboardDataSetInstanceObject($dashboardDataSetInstance, $additionalTransformations = []) {
        return $this->datasetService->getEvaluatedDataSetForDataSetInstance($dashboardDataSetInstance, $additionalTransformations);
    }


    /**
     * Get all dashboards containing alerts which match the supplied group id.
     *
     * @param $alertGroupId
     * @return ActiveDashboardDatasetAlerts[]
     */
    public function getActiveDashboardDatasetAlertsMatchingAlertGroup($alertGroupId) {

        /**
         * Get all dashboards with dataset alerts which match the alert group
         * @var Dashboard[] $matchingDashboards
         */
        $matchingDashboards = Dashboard::filter("WHERE alertsEnabled 
                AND datasetInstances.alerts.alert_group_id = ?", $alertGroupId);

        $activeDashboardDatasetAlerts = [];
        foreach ($matchingDashboards as $dashboard) {
            foreach ($dashboard->getDatasetInstances() as $datasetInstance) {
                $activeAlerts = [];
                foreach ($datasetInstance->getAlerts() as $alert) {
                    if ($alert->isEnabled() && $alert->getAlertGroupId() == $alertGroupId)
                        $activeAlerts[] = $alert;
                }
                if (sizeof($activeAlerts)) {
                    $activeDashboardDatasetAlerts[] = new ActiveDashboardDatasetAlerts($datasetInstance, $activeAlerts);
                }
            }
        }

        return $activeDashboardDatasetAlerts;
    }


    /**
     * Get a dashboard by id including parent merge
     *
     * @param $id
     * @return Dashboard
     */
    private function getRecursiveDashboardById($id) {

        /**
         * @var Dashboard $dashboard
         */
        $dashboard = Dashboard::fetch($id);

        if ($dashboard->getParentDashboardId()) {
            $this->mergeParentDashboard($dashboard, $dashboard->getParentDashboardId());
        }

        return $dashboard;
    }


    /**
     * Merge a child dashboard object with it's parent using the supplied id.
     *
     * @param Dashboard $childDashboard
     * @param int $parentDashboardId
     */
    private function mergeParentDashboard($childDashboard, $parentDashboardId) {

        try {
            $parentDashboard = $this->getRecursiveDashboardById($parentDashboardId);

            // Capture parent instance keys
            $parentInstanceKeys = ObjectArrayUtils::getMemberValueArrayForObjects("instanceKey", $parentDashboard->getDatasetInstances());

            // Merge instances prioritising keys from parent
            $childDashboard->setDatasetInstances($this->combineArraysByUniqueMemberKey($childDashboard->getDatasetInstances() ?? [],
                $parentDashboard->getDatasetInstances() ?? [], "instanceKey"));

            $parentLayoutSettings = $parentDashboard->getLayoutSettings() ?? [];
            $childLayoutSettings = $childDashboard->getLayoutSettings() ?? [];

            $layoutSettings = [];

            // Calculate grid settings
            $gridSettings = [];
            foreach ($parentLayoutSettings["grid"] ?? [] as $parentGridSetting) {
                $parentGridSetting["locked"] = 1;
                $gridSettings[] = $parentGridSetting;
            }

            foreach ($childLayoutSettings["grid"] ?? [] as $childGridSetting) {
                preg_match('/id="(.*?)"/', $childGridSetting["content"], $matches);
                $instanceKey = $matches[1] ?? null;
                if ($instanceKey && !in_array($instanceKey, $parentInstanceKeys))
                    $gridSettings[] = $childGridSetting;
            }

            $layoutSettings["grid"] = $gridSettings;


            $combinedKeys = array_unique(array_merge(array_keys($parentLayoutSettings), array_keys($childLayoutSettings)));
            foreach ($combinedKeys as $key) {
                if ($key !== "grid") {
                    $layoutSettings[$key] = array_merge($parentLayoutSettings[$key] ?? [], $childLayoutSettings[$key] ?? []);
                }
            }

            $childDashboard->setLayoutSettings($layoutSettings);


        } catch (ObjectNotFoundException $e) {
            // Tolerate broken hierarchy and ignore parentage in this scenario
        }
    }


    /**
     * Remove parent data from a dashboard
     *
     * @param Dashboard $dashboard
     */
    private function removeParentData($dashboard) {

        $newInstances = [];
        $includeKeys = [];

        // Remove parent instances
        foreach ($dashboard->getDatasetInstances() ?? [] as $datasetInstance) {
            if (!$datasetInstance->getDashboardId() || $datasetInstance->getDashboardId() == $dashboard->getId()) {
                $newInstances[] = $datasetInstance;
                $includeKeys[$datasetInstance->getInstanceKey()] = 1;
            }
        }
        $dashboard->setDatasetInstances($newInstances);


        // Update layout settings
        $layoutSettings = $dashboard->getLayoutSettings() ?? [];

        // Grid first
        $gridSettings = $layoutSettings["grid"] ?? [];
        $newGrid = [];
        foreach ($gridSettings as $gridSetting) {
            if (!($gridSetting["locked"] ?? false))
                $newGrid[] = $gridSetting;
        }
        $layoutSettings["grid"] = $newGrid;


        // Grab parent dashboard and optimise out any parameters exact match to parent ones
        $parentDashboard = $this->getRecursiveDashboardById($dashboard->getParentDashboardId());
        $parentLayoutSettings = $parentDashboard->getLayoutSettings() ?? [];

        $parameters = $layoutSettings["parameters"] ?? [];
        foreach ($parentLayoutSettings["parameters"] ?? [] as $key => $parameter) {
            if (isset($parameters[$key]) && $parameters[$key] == $parameter) {
                unset($parameters[$key]);
            }
        }
        $layoutSettings["parameters"] = $parameters;

        // Everything else except grid and parameters
        foreach ($layoutSettings as $key => $value) {
            if ($key !== "grid" && $key !== "parameters") {
                $layoutSettings[$key] = array_intersect_key($layoutSettings[$key], $includeKeys);
            }
        }

        // Update layout settings
        $dashboard->setLayoutSettings($layoutSettings);

    }



    // Combine the two arrays using a member key to determine uniqueness.
    // Second array keys win if conflicts
    private function combineArraysByUniqueMemberKey($firstArray, $secondArray, $memberKey) {
        $firstArrayIndexed = ObjectArrayUtils::indexArrayOfObjectsByMember($memberKey, $firstArray);
        $secondArrayIndexed = ObjectArrayUtils::indexArrayOfObjectsByMember($memberKey, $secondArray);
        $combined = array_merge($firstArrayIndexed, $secondArrayIndexed);
        return array_values($combined);
    }


}
