<?php

namespace Kinintel\Traits\Controller\Account;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Services\Hook\DatasourceHookService;

/**
 * @package Kinintel\Controllers\Account
 */
trait Hook {

    private $hookService;

    // ToDo: Add any privileges

    public function __construct() {
        $this->hookService = Container::instance()->get(DatasourceHookService::class);
    }

    /**
     * @http POST /create
     *
     * @param string $datasourceInstanceKey
     * @param string $hookMode
     * @param string $processorType
     * @param string $config
     */
    public function create($datasourceInstanceKey, $hookMode, $processorType, $config) {
        $this->hookService->createHook($datasourceInstanceKey, $hookMode, $processorType, $config);
    }

    /**
     * @http GET /$hookKey
     */
    public function getHook($hookKey) {
        return $this->hookService->getDatasourceHookByKey($hookKey);
    }

    /**
     * @http GET /
     *
     * @param string $datasourceInstanceKey
     */
    public function getAll($datasourceInstanceKey) {
        return $this->hookService->getDatasourceHookInstancesForDatasourceInstance($datasourceInstanceKey);
    }

    /**
     * @http DELETE /$key
     *
     * @param $hookKey
     */
    public function deleteHook($hookKey) {
        $this->hookService->deleteHook($hookKey);
    }

}