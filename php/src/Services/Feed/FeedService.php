<?php


namespace Kinintel\Services\Feed;


use Kiniauth\Objects\Account\Account;
use Kinintel\Exception\FeedNotFoundException;
use Kinintel\Objects\Feed\Feed;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Services\Dataset\DatasetService;

class FeedService {

    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * FeedService constructor.
     *
     * @param DatasetService $datasetService
     */
    public function __construct($datasetService) {
        $this->datasetService = $datasetService;
    }


    /**
     * Get a single feed by id
     *
     * @param $id
     * @return FeedSummary
     */
    public function getFeedById($id) {
        return Feed::fetch($id)->returnSummary();
    }

    /**
     * Filter the list of feeds optionally by a filter string and for a specific project
     *
     * @param string $filterString
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param string $accountId
     *
     * @return FeedSummary[]
     */
    public function filterFeeds($filterString = "", $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $whereClauses = [];
        $params = [];

        if ($filterString) {
            $whereClauses[] = "(path LIKE ? OR datasetLabel.title LIKE ?)";
            $params[] = "%$filterString%";
            $params[] = "%$filterString%";
        }

        if ($accountId) {
            $whereClauses[] = "accountId = ?";
            $params[] = $accountId;
        }

        if ($projectKey) {
            $whereClauses[] = "projectKey = ?";
            $params[] = $projectKey;
        }

        $query = (sizeof($whereClauses) ? "WHERE " : "") . join(" AND ", $whereClauses) . " ORDER BY path";

        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $query .= " OFFSET ?";
            $params[] = $offset;
        }

        $results = Feed::filter($query, $params);
        return array_map(function ($item) {
            return $item->returnSummary();
        }, $results);

    }


    /**
     * Check if a feed url is available (useful for client validation).
     */
    public function isFeedURLAvailable($feedUrl, $currentItemId = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Use feed validator
        $feed = new Feed(new FeedSummary($feedUrl, null, null, null, null, $currentItemId), null, $accountId);
        return sizeof($feed->validate()) == 0;
    }

    /**
     * Save a feed, optionally
     *
     * @param FeedSummary $feed
     * @param string $projectKey
     * @param string $accountId
     */
    public function saveFeed($feed, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        /**
         * Create a real feed, save
         */
        $feed = new Feed($feed, $projectKey, $accountId);
        $feed->save();

        return $feed->getId();
    }


    /**
     * Remove a feed by id
     *
     * @param $feedId
     */
    public function removeFeed($feedId) {
        $feed = Feed::fetch($feedId);
        $feed->remove();
    }


    /**
     * Evaluate a feed by path, passing parameter values as supplied from call
     *
     * @param string $feedPath
     * @param array $parameterValues
     */
    public function evaluateFeed($feedPath, $parameterValues = [], $offset = 0, $limit = 50) {

        // Check matching feeds
        $matchingFeeds = Feed::filter("WHERE path = ?", $feedPath);
        if (sizeof($matchingFeeds) == 0) {
            throw new FeedNotFoundException($feedPath);
        }

        /**
         * @var Feed $feed
         */
        $feed = $matchingFeeds[0];

        // Grab the data set instance summary for this feed
        $datasetInstanceSummary = $this->datasetService->getDataSetInstance($feed->getDatasetInstanceId());

        // Ensure we fill all parameter values with exposed parameter values
        foreach ($feed->getExposedParameterNames() as $exposedParameterName) {
            if (!isset($parameterValues[$exposedParameterName])) {
                $parameterValues[$exposedParameterName] = "";
            }
        }

        // Export and return result directly
        return $this->datasetService->exportDatasetInstance($datasetInstanceSummary, $feed->getExporterKey(), $feed->getExporterConfiguration(), $parameterValues, [], $offset, $limit, false);

    }


}