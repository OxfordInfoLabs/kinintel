<?php

namespace Kinintel\Traits\Controller\Admin;

use Kinikit\Persistence\ORM\Query\SummarisedValue;
use Kinintel\Services\Application\DataSearchService;
use Kinintel\ValueObjects\Application\DataSearchItem;

trait DataSearch {
    public function __construct(
        private DataSearchService $dataSearchService
    ) {
    }

    /**
     * Search for data items matching a set of filters for the supplied account and
     * optionally project
     *
     * @http POST /
     *
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @param string $projectKey
     *
     * @return DataSearchItem[]
     */
    public function searchForDataItems($filters = [], $offset = 0, $limit = 100, $projectKey = null) {
        return $this->dataSearchService->searchForAccountDataItems($filters, $limit, $offset, $projectKey, 0);
    }


    /**
     * @http GET /types
     *
     * @param $searchTerm
     * @param $projectKey
     * @return SummarisedValue[]
     */
    public function getMatchingDataItemTypesForSearchTerm($searchTerm, $projectKey = null) {
        return $this->dataSearchService->getMatchingAccountDataItemTypesForSearchTerm($searchTerm, $projectKey, 0);
    }
}