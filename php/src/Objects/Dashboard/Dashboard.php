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
                $dashboardSummary->isAlertsEnabled(), $dashboardSummary->getSummary(), $dashboardSummary->getDescription(), $dashboardSummary->getCategories(), $dashboardSummary->getId());
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
            $returnCopy ? null : $this->id, $readOnly);

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
