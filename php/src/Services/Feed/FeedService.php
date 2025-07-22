<?php


namespace Kinintel\Services\Feed;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\Security\Captcha\GoogleRecaptchaProvider;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\MVC\Request\Request;
use Kinintel\Exception\FeedNotFoundException;
use Kinintel\Objects\Feed\Feed;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Objects\Feed\PushFeed;
use Kinintel\Objects\Feed\PushFeedSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Dataset\Exporter\JSONContentSource;
use Kinintel\ValueObjects\Feed\PushFeedConfig;

class FeedService {

    /**
     * @param DatasetService $datasetService
     * @param SecurityService $securityService
     * @param GoogleRecaptchaProvider $captchaProvider
     */
    public function __construct(
        private DatasetService          $datasetService,
        private SecurityService         $securityService,
        private GoogleRecaptchaProvider $captchaProvider,
        private QueuedTaskService       $queuedTaskService,
        private HttpRequestDispatcher   $httpRequestDispatcher,
        private NotificationService     $notificationService
    ) {
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
     * Get a feed by path
     *
     * @param $path
     *
     * @return Feed
     */
    public function getFeedByPath($feedPath) {
        // Check matching feeds
        $matchingFeeds = Feed::filter("WHERE path = ?", $feedPath);
        if (sizeof($matchingFeeds) == 0) {
            throw new FeedNotFoundException($feedPath);
        }

        $feed = $matchingFeeds[0];

        return $feed;

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
        $feed = new Feed(new FeedSummary($feedUrl, null, null, null, null, 0, null, $currentItemId), null, $accountId);
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
     * @param int $offset
     * @param int $limit
     * @param Request $request
     */
    public function evaluateFeedByPath($feedPath, $parameterValues = [], $offset = 0, $limit = 50, $request = null) {

        // Get feed by path and evaluate as object
        $feed = $this->getFeedByPath($feedPath);
        return $this->evaluateFeed($feed, $parameterValues, $offset, $limit, $request);

    }


    /**
     * Filter push feeds by feed path and limit and offset.
     *
     * @param ?string $feedPath
     * @param ?string $projectKey
     * @param ?int $offset
     * @param ?int $limit
     * @param mixed $accountId
     * @return PushFeedSummary[]
     */
    public function filterPushFeeds(?string $feedPath = null, ?string $projectKey = null, ?int $offset = 0, ?int $limit = 10, mixed $accountId = Account::LOGGED_IN_ACCOUNT): array {

        // Construct dynamic clauses as required.
        $filters = ["accountId = ?"];
        $params = [$accountId];
        if ($feedPath) {
            $filters[] = "feedPath = ?";
            $params[] = $feedPath;
        }

        if ($projectKey) {
            $filters[] = "projectKey = ?";
            $params[] = $projectKey;
        }

        // Add offset and limits
        $params[] = $limit;
        $params[] = $offset;

        return array_map(function ($pushFeed) {
            return $pushFeed->generateSummary();
        },
            PushFeed::filter("WHERE " . join(" AND ", $filters) . " LIMIT ? OFFSET ?", $params));


    }


    /**
     * Save a push feed and return the id.
     *
     * @param PushFeedSummary $pushFeed
     * @param ?string $projectKey
     * @param mixed $accountId
     *
     * @return int
     */
    public function savePushFeed(PushFeedSummary $pushFeedSummary, ?string $projectKey = null,
                                 mixed           $accountId = Account::LOGGED_IN_ACCOUNT) {

        $pushFeed = new PushFeed($pushFeedSummary, $projectKey, $accountId);
        $pushFeed->save();

        return $pushFeed->getId();

    }

    /**
     * Remove a push feed by id
     *
     * @param int $pushFeedId
     */
    public function removePushFeed(int $pushFeedId) {

        $pushFeed = PushFeed::fetch($pushFeedId);
        $pushFeed->remove();

    }


    /**
     * Queue push feed by id
     *
     * @param int $pushFeedId
     * @return void
     */
    public function queuePushFeed(int $pushFeedId) {

        // Grab the push feed
        $pushFeed = PushFeedSummary::fetch($pushFeedId);

        // Construct the task description from params
        $paramsHash = md5(join(":", array_values($pushFeed->getFeedParameterValues() ?? [])));
        $taskDescription = "Push Feed -> " . $pushFeed->getPushUrl() . " (" . $paramsHash . ")";


        // Check no existing task with the same description and if not, queue
        $tasks = $this->queuedTaskService->listQueuedTasks("push-feed");
        $indexedTasks = ObjectArrayUtils::indexArrayOfObjectsByMember("description", $tasks);

        if (!($indexedTasks[$taskDescription] ?? null))
            // Queue the task
            $this->queuedTaskService->queueTask("push-feed", "pushfeed", $taskDescription, ["pushFeedId" => $pushFeedId], null, 0);


    }


    /**
     * Process push feed by id.
     *
     * @param $pushFeedId
     * @return void
     */
    public function processPushFeed($pushFeedId) {

        /**
         * @var PushFeed $pushFeed
         */
        $pushFeed = PushFeed::fetch($pushFeedId);

        // Construct feed request
        $feedParams = $pushFeed->getFeedParameterValues();
        $lastQueriedValue = $pushFeed->getLastPushedSequenceValue() ?? $pushFeed->getInitialSequenceValue();

        if ($pushFeed->getFeedSequenceParameterKey()) {
            $feedParams[$pushFeed->getFeedSequenceParameterKey()] = $lastQueriedValue;

        }


        // Execute the feed
        $feedResponse = $this->evaluateFeedByPath($pushFeed->getFeedPath(), $feedParams, 0, 1000);

        if ($feedResponse->getContentSource() instanceof JSONContentSource) {

            $data = $feedResponse->getContentSource()->getData();

            // If a feed sequence result field name, use this
            if ($pushFeed->getFeedSequenceResultFieldName()) {
                while (($data[0][$pushFeed->getFeedSequenceResultFieldName()] ?? null) <= $lastQueriedValue)
                    array_shift($data);
            }

            // Construct headers
            $headers = new Headers([Headers::CONTENT_TYPE => "text/json"]);

            // Create the request
            $request = new \Kinikit\Core\HTTP\Request\Request($pushFeed->getPushUrl(), $pushFeed->getMethod(),
                [], json_encode($data, JSON_INVALID_UTF8_IGNORE), $headers);


            // Send it off
            $pushResponse = $this->httpRequestDispatcher->dispatch($request);

            if ($pushResponse->getStatusCode() < 200 || $pushResponse->getStatusCode() >= 400) {

                if ($pushFeed->getNotificationGroups() ?? null){
                    
                }


            } else {

                // Update push feed
                $pushFeed->setLastPushedSequenceValue(array_pop($data)[$pushFeed->getFeedSequenceResultFieldName()] ?? null);
                $pushFeed->save();
            }
        }

    }


    /**
     * Evaluate a feed object
     *
     * @param Feed $feed
     * @param Request|null $request
     * @param array $parameterValues
     * @param int $limit
     * @param int $offset
     * @return \Kinikit\MVC\Response\Download|\Kinikit\MVC\Response\Response|\Kinikit\MVC\Response\SimpleResponse
     * @throws AccessDeniedException
     * @throws \Kiniauth\Exception\Security\MissingScopeObjectIdForPrivilegeException
     * @throws \Kiniauth\Exception\Security\NonExistentPrivilegeException
     */
    private function evaluateFeed(Feed $feed, array $parameterValues = [], int $offset = 0, int $limit = 50, ?Request $request = null): \Kinikit\MVC\Response\Download|\Kinikit\MVC\Response\Response|\Kinikit\MVC\Response\SimpleResponse {


        // Check access granted to evaluate the feed
        if ($feed->getProjectKey() && !$this->securityService->checkLoggedInHasPrivilege(Role::SCOPE_PROJECT, "feedaccess", $feed->getProjectKey())) {
            throw new AccessDeniedException("You have not been granted access to feeds");
        }

        // If we have referring domains, check these now.
        if ($feed->getWebsiteConfig()->getReferringDomains()) {
            if (!$request || !$request->getReferringURL())
                throw new AccessDeniedException("Invalid website referrer supplied for Feed");

            foreach ($feed->getWebsiteConfig()->getReferringDomains() as $referringDomain) {
                $found = false;
                if ($request->getReferringURL()->getHost() == $referringDomain) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new AccessDeniedException("Invalid website referrer supplied for Feed");
            }

        }

        // If we require a captcha, confirm this now
        if ($feed->getWebsiteConfig()->isRequiresCaptcha()) {
            if (!$request || !$request->getHeaders()->getCustomHeader("X_CAPTCHA_TOKEN"))
                throw new AccessDeniedException("Captcha required but not supplied");

            $captchaKey = $request->getHeaders()->getCustomHeader("X_CAPTCHA_TOKEN");

            // Verify the captcha
            $this->captchaProvider->setRecaptchaSecretKey($feed->getWebsiteConfig()->getCaptchaSecretKey());
            $this->captchaProvider->setRecaptchaScoreThreshold($feed->getWebsiteConfig()->getCaptchaScoreThreshold());

            if (!$this->captchaProvider->verifyCaptcha($captchaKey, $request)) {
                throw new AccessDeniedException("Invalid Captcha Supplied for Feed");
            }
        }


        // Grab the data set instance summary for this feed
        $datasetInstanceSummary = $this->datasetService->getDataSetInstance($feed->getDatasetInstanceId());

        // Ensure we fill all parameter values with exposed parameter values
        $exportParameters = [];
        foreach ($feed->getExposedParameterNames() as $exposedParameterName) {

            if (isset($parameterValues[$exposedParameterName])) {
                // Empty string doesn't survive str_get_csv
                if ($parameterValues[$exposedParameterName] === "") {
                    $exportParameters[$exposedParameterName] = "";
                } else {
                    $parameterValue = str_getcsv($parameterValues[$exposedParameterName], ",", '"');
                    $exportParameters[$exposedParameterName] = sizeof($parameterValue) == 1 ? $parameterValue[0] : $parameterValue;
                }
            } else {
                $exportParameters[$exposedParameterName] = "";
            }

        }

        // Limit the limit
        if ($limit > 10000) {
            $limit = 10000;
        }

        // Export and return result directly
        return $this->datasetService->exportDatasetInstance($datasetInstanceSummary, $feed->getExporterKey(), $feed->getExporterConfiguration(), $exportParameters, [], $offset, $limit, false, $feed->getCacheTimeSeconds());
    }


}