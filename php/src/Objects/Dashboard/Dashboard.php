<?php

namespace Kinintel\Objects\Dashboard;

use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\ObjectCategory;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\DependencyInjection\Container;
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
     * @var ObjectCategory[]
     * @oneToMany
     * @childJoinColumns object_id, object_type=KiDashboard
     */
    protected $categories = [];

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
                $dashboardSummary->isAlertsEnabled(), $dashboardSummary->getSummary(), $dashboardSummary->getDescription(), $dashboardSummary->getCategories(), $dashboardSummary->getId(), false, $dashboardSummary->getParentDashboardId());
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
     * @return ObjectCategory[]
     */
    public function getCategories() {
        return $this->categories;
    }

    /**
     * @param ObjectCategory[] $categories
     */
    public function setCategories($categories) {
        $this->categories = $categories;
    }


    /**
     * Override save method to ensure all parent data is removed first of all
     */
    public function save() {

        // If a parent dashboard, firstly strip all parent data
        if ($this->parentDashboardId) {
            $this->removeParentData();
        }

        parent::save();
    }


    /**
     * Return a dashboard summary
     */
    public function returnSummary($returnCopy = false) {

        /**
         * @var SecurityService $securityService
         */
        $securityService = Container::instance()->get(SecurityService::class);
        $readOnly = !$returnCopy && !$securityService->isSuperUserLoggedIn() && $this->accountId == null;


        // Map categories to summary objects
        $newCategories = [];
        foreach ($this->categories as $category) {
            if ($category instanceof ObjectCategory) {
                $newCategories[] = new CategorySummary($category->getCategory()->getCategory(), $category->getCategory()->getDescription(), $category->getCategory()->getKey());
            } else if ($category instanceof CategorySummary) {
                $newCategories[] = $category;
            }
        }

        $dashboardSummary = new DashboardSummary($this->title, $this->datasetInstances, $this->displaySettings, $this->layoutSettings,
            $this->alertsEnabled,
            $this->summary, $this->description, $newCategories,
            $returnCopy ? null : $this->id, $readOnly, $this->parentDashboardId);

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

    // Remove parent data
    private function removeParentData() {

        $newInstances = [];
        $includeKeys = [];

        // Remove parent instances
        foreach ($this->datasetInstances ?? [] as $datasetInstance) {
            if (!$datasetInstance->getDashboardId() || $datasetInstance->getDashboardId() == $this->id) {
                $newInstances[] = $datasetInstance;
                $includeKeys[$datasetInstance->getInstanceKey()] = 1;
            }
        }
        $this->datasetInstances = $newInstances;


        // Update layout settings
        $layoutSettings = $this->layoutSettings ?? [];

        // Grid first
        $gridSettings = $layoutSettings["grid"] ?? [];
        $newGrid = [];
        foreach ($gridSettings as $gridSetting) {
            if (!($gridSetting["locked"] ?? false))
                $newGrid[] = $gridSetting;
        }
        $layoutSettings["grid"] = $newGrid;

        // Everything else except grid
        foreach ($layoutSettings as $key => $value) {
            if ($key !== "grid") {
                $layoutSettings[$key] = array_intersect_key($layoutSettings[$key], $includeKeys);
            }
        }

        // Update layout settings
        $this->layoutSettings = $layoutSettings;
    }

}
