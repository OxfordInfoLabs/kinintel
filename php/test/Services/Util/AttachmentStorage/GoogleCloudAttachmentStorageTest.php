<?php


namespace Kinintel\Test\Services\Util\AttachmentStorage;

use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\Services\Util\AttachmentStorage\GoogleCloudAttachmentStorage;
use Kinintel\Services\Util\GoogleCloudStorageService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Authentication\Google\GoogleCloudCredentials;

include_once "autoloader.php";

class GoogleCloudAttachmentStorageTest extends TestBase {

    /**
     * @var MockObject
     */
    private $googleCloudStorageService;


    /**
     * @var MockObject
     */
    private $authenticationCredentialsService;

    /**
     * @var GoogleCloudAttachmentStorage
     */
    private $attachmentStorage;

    // Set up
    public function setUp(): void {
        $this->googleCloudStorageService = MockObjectProvider::instance()->getMockInstance(GoogleCloudStorageService::class);
        $this->authenticationCredentialsService = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsService::class);
        $mockCredentialsInstance = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsInstance::class);
        $mockCredentials = MockObjectProvider::instance()->getMockInstance(GoogleCloudCredentials::class);
        $mockCredentialsInstance->returnValue("returnCredentials", $mockCredentials);
        $mockCredentials->returnValue("getJsonString", "TEST JSON STRING");
        $this->authenticationCredentialsService->returnValue("getCredentialsInstanceByKey", $mockCredentialsInstance, "google-cloud");
        $this->attachmentStorage = new GoogleCloudAttachmentStorage($this->authenticationCredentialsService);
        $this->attachmentStorage->setGoogleCloudStorageService($this->googleCloudStorageService);
    }

    public function testStorageFunctionsUseTheGoogleCloudStorageServiceAsExpected() {

        $attachment = new AttachmentSummary("mytest.txt", "test/html", "Test", "mytest1", "test", "firstproject", 55, 12);

        // Save content
        $this->attachmentStorage->saveAttachmentContent($attachment, new ReadOnlyStringStream("TEST ATTACHMENT"));

        $this->assertTrue($this->googleCloudStorageService->methodWasCalled("saveObject", [
            "attachments", "account/55/firstproject/Test/mytest1/12-mytest.txt", "TEST ATTACHMENT"
        ]));

        $this->googleCloudStorageService->returnValue("getObject", "UPDATED ATTACHMENT", [
            "attachments", "account/55/firstproject/Test/mytest1/12-mytest.txt"
        ]);

        // Get content
        $result = $this->attachmentStorage->getAttachmentContent($attachment);

        $this->assertEquals("UPDATED ATTACHMENT", $result);

    }


}