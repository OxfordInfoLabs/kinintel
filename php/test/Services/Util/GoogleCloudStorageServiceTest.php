<?php


namespace Kinintel\Test\Services\Util;

use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\Connection\ConnectionInterface;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Services\Util\GoogleCloudStorageService;

include_once "autoloader.php";

class GoogleCloudStorageServiceTest extends \PHPUnit\Framework\TestCase {


    /**
     * @var MockObject
     */
    private $storageClient;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var MockObject
     */
    private $bucket;

    /**
     * @var MockObject
     */
    private $object;

    public function setUp(): void {

//        $this->storageClient = new StorageClient([
//            "keyFile" => json_decode(file_get_contents(__DIR__ . "/google_creds.json"), true)]);
//
//        if ($this->storageClient->bucket("dap-unit-test")->object("test.txt")->exists()) {
//            $this->storageClient->bucket("dap-unit-test")->object("test.txt")->delete();
//        }

        $this->storageClient = MockObjectProvider::instance()->getMockInstance(StorageClient::class);

        $this->connection = MockObjectProvider::instance()->getMockInstance(ConnectionInterface::class);

        $this->bucket = MockObjectProvider::instance()->getMockInstance(Bucket::class, ["connection" => $this->connection, "name" => "test"]);
        $this->storageClient->returnValue("bucket", $this->bucket, ["dap-unit-test"]);

        $this->object = MockObjectProvider::instance()->getMockInstance(StorageObject::class, ["connection" => $this->connection, "name" => "text", "bucket" => "dap-unit-test", "info" => []]);
        $this->bucket->returnValue("object", $this->object, ["test.txt"]);

        $this->object->returnValue("downloadAsString", "HELLO WORLD");

    }


    public function testItemNotFoundExceptionRaisedIfNoneExistentFileAccessed() {

        $cloudStorageService = new GoogleCloudStorageService();
        $cloudStorageService->setStorageClient($this->storageClient);

        $this->bucket->throwException("object", new NotFoundException(), ["idontexist.txt"]);


        try {
            $cloudStorageService->getObject("dap-unit-test", "idontexist.txt");
            $this->fail("Should have thrown here");
        } catch (ItemNotFoundException $e) {
            $this->assertTrue(true);
        }

    }

    public function testCanStoreAndReadFileInGoogleCloudBucket() {

        $cloudStorageService = new GoogleCloudStorageService();
        $cloudStorageService->setStorageClient($this->storageClient);

        $this->bucket->returnValue("object", "");

        // Save an object
        $cloudStorageService->saveObject("dap-unit-test", "test.txt", "HELLO WORLD");
        $this->assertTrue($this->bucket->methodWasCalled("upload", ["HELLO WORLD", ["name" => "test.txt"]]));

        // Read it again
        $reObject = $cloudStorageService->getObject("dap-unit-test", "test.txt");
        $this->assertTrue($this->bucket->methodWasCalled("object", "test.txt"));
        $this->assertTrue($this->object->methodWasCalled("downloadAsString"));

        $this->assertEquals("HELLO WORLD", $reObject);

    }


    public function testCanConfirmIfObjectExistsOrNot() {

        $cloudStorageService = new GoogleCloudStorageService();
        $cloudStorageService->setStorageClient($this->storageClient);

        $this->object->returnValue("exists", true);
        $this->assertTrue($cloudStorageService->objectExists("dap-unit-test", "test.txt"));

        $this->object->returnValue("exists", false);
        $this->assertFalse($cloudStorageService->objectExists("dap-unit-test", "test.txt"));

    }

}