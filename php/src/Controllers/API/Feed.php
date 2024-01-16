<?php


namespace Kinintel\Controllers\API;


use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Response\Response;
use Kinintel\Services\Feed\FeedService;


class Feed {

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
     *
     * Handle all feed requests
     *
     * @param Request $request
     * @return Response
     */
    public function handleRequest($request) {

        $explodedPath = explode("feed/", $request->getUrl()->getPath(), 2);
        if (sizeof($explodedPath) > 1) {
            $limit = $request->getParameter("limit") ?? 50;
            $offset = $request->getParameter("offset") ?? 0;
            return $this->feedService->evaluateFeed($explodedPath[1], $request->getParameters(), $offset, $limit, $request);
        }

    }

}