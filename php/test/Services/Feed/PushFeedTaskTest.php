<?php

namespace Kinintel\Test\Services\Feed;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Services\Feed\FeedService;
use Kinintel\Services\Feed\PushFeedTask;
use Kinintel\TestBase;

include_once "autoloader.php";

class PushFeedTaskTest extends TestBase {


    /**
     * @var FeedService|MockObject
     */
    private $service;


    public function setUp(): void {
        $this->service = MockObjectProvider::mock(FeedService::class);
    }

    public function testRunCallsExecutePushFeed() {

        $pushFeedTask = new PushFeedTask($this->service);
        $pushFeedTask->run(["pushFeedId" => 99]);

        $this->assertTrue($this->service->methodWasCalled("executePushFeed", [99]));

    }

}