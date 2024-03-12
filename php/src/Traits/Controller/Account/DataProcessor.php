<?php

namespace Kinintel\Traits\Controller\Account;

use Kiniauth\Services\Security\SecurityService;
use Kinikit\Persistence\ORM\Query\Filter\LikeFilter;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\ValueObjects\DataProcessor\DataProcessorItem;

trait DataProcessor {


    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;

    /**
     * @var SecurityService
     */
    private $securityService;


    /**
     * @param DataProcessorService $dataProcessorService
     * @param SecurityService $securityService
     */
    public function __construct(DataProcessorService $dataProcessorService, SecurityService $securityService) {
        $this->dataProcessorService = $dataProcessorService;
        $this->securityService = $securityService;
    }


    /**
     * Return data processor item for a key
     *
     * @http GET /$key
     *
     * @param $key
     * @return DataProcessorItem
     */
    public function getDataProcessorInstance($key) {
        return DataProcessorItem::fromDataProcessorInstance($this->dataProcessorService->getDataProcessorInstance($key));
    }


    /**
     *
     * @http GET /type/$type
     *
     * @param string $type
     * @param string $searchTerm
     * @param string $projectKey
     * @param integer $offset
     * @param integer $limit
     *
     * @return DataProcessorItem[]
     */
    public function filterProcessorInstancesByType($type, $searchTerm = "", $projectKey = null, $offset = 0, $limit = 10) {
        $matches = $this->dataProcessorService->filterDataProcessorInstances([
            "type" => new LikeFilter("type", "%" . $type . "%"),
            "search" => $searchTerm
        ], $projectKey, $offset, $limit);

        return array_map(function ($instance) {
            return DataProcessorItem::fromDataProcessorInstance($instance);
        }, $matches);

    }


    /**
     *
     * @http GET /relatedobject/$type/$objectType/$objectPrimaryKey
     *
     * @param string $type
     * @param string $objectType
     * @param string $objectPrimaryKey
     * @param string $searchTerm
     * @param string $projectKey
     * @param integer $offset
     * @param integer $limit
     *
     * @return DataProcessorItem[]
     */
    public function filterProcessorInstancesByTypeAndRelatedObject($type, $objectType, $objectPrimaryKey, $searchTerm = "", $projectKey = null, $offset = 0, $limit = 10) {
        $matches = $this->dataProcessorService->filterDataProcessorInstances([
            "type" => new LikeFilter("type", "%" . $type . "%"),
            "relatedObjectType" => $objectType,
            "relatedObjectPrimaryKey" => $objectPrimaryKey,
            "search" => $searchTerm
        ], $projectKey, $offset, $limit);

        return array_map(function ($instance) {
            return DataProcessorItem::fromDataProcessorInstance($instance);
        }, $matches);

    }


    /**
     * @http PATCH /$key
     *
     * @param $key
     * @return void
     */
    public function triggerProcessorInstance($key) {
        $this->dataProcessorService->triggerDataProcessorInstance($key);
    }


    /**
     * Save a data processor instance
     *
     * @http POST /
     *
     * @param DataProcessorItem $item
     * @param string $projectKey
     * @return int
     */
    public function saveDataProcessorInstance($item, $projectKey) {
        $accountId = $this->securityService->getLoggedInSecurableAndAccount()[1]->getAccountId();
        return $this->dataProcessorService->saveDataProcessorInstance($item->toDataProcessorInstance($projectKey, $accountId));
    }


    /**
     * Remove a data processor instance
     *
     * @http DELETE /
     *
     * @param string $key
     * @return void
     */
    public function removeDataProcessorInstance($key) {
        $this->dataProcessorService->removeDataProcessorInstance($key);
    }


}