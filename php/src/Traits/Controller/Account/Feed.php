<?php


namespace Kinintel\Traits\Controller\Account;


use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Services\Feed\FeedService;

trait Feed {

    /**
     * @var FeedService
     */
    private $feedService;


    /**
     * Feed constructor.
     *
     * @param FeedService $feedService
     */
    public function __construct($feedService) {
        $this->feedService = $feedService;
    }

    /**
     * Get a feed by id
     *
     * @http GET /$id
     *
     * @param $id
     * @return FeedSummary
     */
    public function getFeed($id) {
        return $this->feedService->getFeedById($id);
    }


    /**
     * Filter feeds using supplied params
     *
     * @http GET /
     *
     * @param string $filterString
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     */
    public function filterFeeds($filterString = "", $projectKey = null, $offset = 0, $limit = 10) {
        return $this->feedService->filterFeeds($filterString, $projectKey, $offset, $limit);
    }


    /**
     * Check if a given field url is available - used for validation
     *
     * @http GET /available
     *
     * @param string $feedUrl
     * @param int $currentItemId
     */
    public function isFeedURLAvailable($feedUrl, $currentItemId = null) {
        return $this->feedService->isFeedURLAvailable($feedUrl, $currentItemId);
    }


    /**
     * Save a feed, optionally with a project key
     *
     * @http POST /
     *
     * @param FeedSummary $feed
     * @param string $projectKey
     *
     * @return int
     */
    public function saveFeed($feed, $projectKey = null) {
        return $this->feedService->saveFeed($feed, $projectKey);
    }


    /**
     * Remove a feed by id
     *
     * @http DELETE /$feedId
     *
     * @param $feedId
     */
    public function removeFeed($feedId) {
        $this->feedService->removeFeed($feedId);
    }

}