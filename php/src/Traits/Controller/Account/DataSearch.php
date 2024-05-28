<?php

namespace Kinintel\Traits\Controller\Account;

use Kinintel\Services\Application\DataSearchService;
use Kinintel\ValueObjects\Application\DataSearchItem;

trait DataSearch {

    /**
     * Inject the datasearch service
     *
     * @param DataSearchService $dataSearchService
     */
    public function __construct(private DataSearchService $dataSearchService) {
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
        return $this->dataSearchService->searchForAccountDataItems($filters, $limit, $offset, $projectKey);
    }

}