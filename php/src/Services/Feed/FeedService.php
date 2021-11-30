<?php


namespace Kinintel\Services\Feed;


use Kiniauth\Objects\Account\Account;
use Kinintel\Objects\Feed\Feed;
use Kinintel\Objects\Feed\FeedSummary;

class FeedService {


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
     * Evaluate a feed by id, passing parameter values as supplied from call
     *
     * @param $feedId
     * @param array $parameterValues
     */
    public function evaluateFeed($feedId, $parameterValues = []) {

    }


}