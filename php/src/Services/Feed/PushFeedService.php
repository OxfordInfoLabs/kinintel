<?php

namespace Kinintel\Services\Feed;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\Security\KeyPairService;
use Kiniauth\Services\Workflow\Task\Queued\QueuedTaskService;
use Kiniauth\ValueObjects\Security\KeyPairSigningOutputFormat;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\Template\KinibindTemplateParser;
use Kinikit\Core\Template\TemplateParser;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Feed\PushFeed;
use Kinintel\Objects\Feed\PushFeedSummary;
use Kinintel\Services\Dataset\Exporter\JSONContentSource;

class PushFeedService {

    /**
     * @var TemplateParser
     */
    private $templateParser;

    /**
     * Construct with required properties
     *
     * @param FeedService $feedService
     * @param QueuedTaskService $queuedTaskService
     * @param HttpRequestDispatcher $httpRequestDispatcher
     * @param NotificationService $notificationService
     */
    public function __construct(private FeedService           $feedService,
                                private QueuedTaskService     $queuedTaskService,
                                private HttpRequestDispatcher $httpRequestDispatcher,
                                private NotificationService   $notificationService,
                                private KeyPairService        $keyPairService) {

        $this->templateParser = new KinibindTemplateParser("d", ["[[", "]]"]);
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
        $feedResponse = $this->feedService->evaluateFeedByPath($pushFeed->getFeedPath(), $feedParams, 0, 1000);

        if ($feedResponse->getContentSource() instanceof JSONContentSource) {

            $data = $feedResponse->getContentSource()->getData();

            // If a feed sequence result field name, use this
            if ($pushFeed->getFeedSequenceResultFieldName()) {
                while (($data[0][$pushFeed->getFeedSequenceResultFieldName()] ?? null) <= $lastQueriedValue)
                    array_shift($data);
            }

            // Construct headers
            $dateHeaderValue = date(\DateTimeInterface::RFC7231);
            $headers = [Headers::CONTENT_TYPE => "application/json", Headers::DATE => $dateHeaderValue];

            // Add additional headers
            if ($pushFeed->getOtherHeaders()) {
                $headers = array_merge($headers, $pushFeed->getOtherHeaders());
            }

            // Encode the data
            $encodedData = json_encode($data, JSON_INVALID_UTF8_IGNORE);

            // If signing with key pair, add the signature and signature-input headers
            if ($pushFeed->getSignWithKeyPairId()) {

                // Create the signature input header
                $headers[Headers::SIGNATURE_INPUT] = 'sig=("method" "content-type" "date" "body");created=' . date("U") . ';alg="rsa-sha512"';

                // Create the signature header
                $signatureRaw = $pushFeed->getMethod() . "\napplication/json\n" . $dateHeaderValue . "\n" . $encodedData;

                $signature = "sig=".$this->keyPairService->signData($signatureRaw, $pushFeed->getSignWithKeyPairId(), KeyPairSigningOutputFormat::Base64);
                $headers[Headers::SIGNATURE] = $signature;

            }



            // Create the request
            $request = new \Kinikit\Core\HTTP\Request\Request($pushFeed->getPushUrl(), $pushFeed->getMethod(),
                [], $encodedData, new Headers($headers));


            // Send it off
            $pushResponse = $this->httpRequestDispatcher->dispatch($request);


            if ($pushResponse->getStatusCode() < 200 || $pushResponse->getStatusCode() >= 400) {

                if ($pushFeed->getNotificationGroups() ?? null) {

                    // Generate a failed notification if required
                    $model = ["pushUrl" => $pushFeed->getPushUrl(), "errorLog" => $pushResponse->getBody()];
                    $title = $this->templateParser->parseTemplateText($pushFeed->getFailedPushNotificationTitle(), $model);
                    $description = $this->templateParser->parseTemplateText($pushFeed->getFailedPushNotificationDescription(), $model);

                    $notificationSummary = new NotificationSummary($title, $description, null,
                        $pushFeed->getNotificationGroups());

                    $this->notificationService->createNotification($notificationSummary, $pushFeed->getProjectKey(), $pushFeed->getAccountId());
                }


            } else {

                // Update push feed
                $pushFeed->setLastPushedSequenceValue(array_pop($data)[$pushFeed->getFeedSequenceResultFieldName()] ?? null);
                $pushFeed->save();
            }
        }

    }


}