<?php


namespace Kinintel\Controllers\API;

use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\MVC\Response\Headers;
use Kinikit\MVC\Response\JSONResponse;
use Kinintel\Exception\ExternalDashboardNotFoundException;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\Services\Dataset\DatasetService;

/**
 * External dashboard API methods
 *
 * Class ExternalDashboard
 * @package Kinintel\Controllers\API
 */
class ExternalDashboard {

    /**
     * @var DashboardService
     */
    private $dashboardService;

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * ExternalDashboard constructor.
     *
     * @param DashboardService $dashboardService
     * @param DatasetService $datasetService
     */
    public function __construct($dashboardService, $datasetService) {
        $this->dashboardService = $dashboardService;
        $this->datasetService = $datasetService;
    }


    /**
     * Get an external dashboard by id
     *
     * @http GET /$id
     *
     * @param $id
     * @return DashboardSummary
     */
    public function getExternalDashboard($id) {
        $dashboard = $this->dashboardService->getDashboardById($id);

        if (!$dashboard->isExternal()) {
            throw new ExternalDashboardNotFoundException($id);
        }

        return $dashboard;
    }


    /**
     * Evaluate a dataset and return a dataset
     *
     * @http POST /evaluateDashboardDataset/$dashboardId/$datasetInstanceKey
     *
     * @param integer $dashboardId
     * @param string $datasetInstanceKey
     * @param mixed $parameterValues
     * @param integer $offset
     * @param integer $limit
     *
     * @return JSONResponse
     */
    public function evaluateDashboardDataset($dashboardId, $datasetInstanceKey, $parameterValues = [], $offset = 0, $limit = 25) {

        // Ensure we have access to the dashboard
        $dashboard = $this->getExternalDashboard($dashboardId);

        // index instances
        $indexedInstances = ObjectArrayUtils::indexArrayOfObjectsByMember("instanceKey", $dashboard->getDatasetInstances());


        // Add caching header
        $headers = [
            Headers::HEADER_CACHE_CONTROL => "public, max-age=" . ($dashboard->getExternalSettings()->isCacheEnabled() ?
                $dashboard->getExternalSettings()->getCacheTimeSeconds() : 0)
        ];


        if ($indexedInstances[$datasetInstanceKey] ?? null) {
            $result = $this->datasetService->getEvaluatedDataSetForDataSetInstance($indexedInstances[$datasetInstanceKey], $parameterValues, [], $offset, $limit);
        } else {
            $result = "";
        }

        return new JSONResponse($result, 200, "application/json", $headers);

    }


}
