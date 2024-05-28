<?php

namespace Kinintel\Services\Application;

use Kiniauth\Objects\Account\Account;
use Kinikit\Core\Util\ArrayUtils;
use Kinikit\Persistence\ORM\Query\Filter\LikeFilter;
use Kinikit\Persistence\ORM\Query\Query;
use Kinintel\Objects\Application\DataSearch;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\ValueObjects\Application\DataSearchItem;
use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorAction;
use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorActions;

/**
 * Cross cutting data search service for finding multiple object types (datasets, datasources, dataprocessors)
 * usually for a given account and project key.
 */
class DataSearchService {

    // Permitted filters
    private const FILTER_MAP = ["type" => "type", "search" => "search"];


    /**
     * Search for account data items.
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @param string|null $projectKey
     * @param mixed $accountId
     *
     * @return DataSearchItem[]
     */
    public function searchForAccountDataItems(array $filters = [], int $limit = 10, int $offset = 0, ?string $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $filters = $this->mapFilters($filters);
        $filters["account_id"] = [$accountId, null];

        $projectKeys = $projectKey ? [$projectKey, null] : [null];
        $filters["project_key"] = $projectKeys;

        $query = new Query(DataSearch::class);
        $results = $query->query($filters, "title", $limit, $offset);

        // Map items to value objects
        return array_map(function ($result) {


            switch ($result->getTypeClass()) {
                case "DataProcessor":
                    $dataProcesorInstance = new DataProcessorInstance($result->getIdentifier(), "", $result->getType(), $result->getConfiguration());
                    $config = $dataProcesorInstance->returnConfig();
                    if (in_array(DataProcessorActions::class, class_uses($config))) {
                        $actionItems = $config->getProcessorActions($result->getIdentifier());
                    } else {
                        $actionItems = [];
                    }
                    break;
                case "Dataset":
                    $actionItems = [new DataProcessorAction("Select", null, $result->getIdentifier())];
                    break;
                case "Datasource":
                    $actionItems = [new DataProcessorAction("Select", $result->getIdentifier())];
                    break;
            }

            return new DataSearchItem($result->getType(), $result->getIdentifier(), $result->getTitle(), $result->getDescription(),
                $actionItems);

        }, $results);

    }


    /**
     * @param array $filters
     * @return array
     */
    private function mapFilters(array $filters): array {
        $filters = ArrayUtils::mapArrayKeys($filters, self::FILTER_MAP);

        if (isset($filters["search"])) {
            $filters["search"] = new LikeFilter(["title", "description"], "%" . $filters["search"] . "%");
        }
        return $filters;
    }


}