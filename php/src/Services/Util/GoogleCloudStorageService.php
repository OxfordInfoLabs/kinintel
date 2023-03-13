<?php


namespace Kinintel\Services\Util;


use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\StorageClient;
use Kinikit\Core\Exception\ItemNotFoundException;

class GoogleCloudStorageService {


    /**
     * @var StorageClient
     */
    private $storageClient;


    /**
     * Construct with credentials
     *
     * GoogleCloudStorageService constructor.
     * @param $credentials
     */
    public function __construct($credentials = null) {
        if ($credentials) {
            $this->setCredentials($credentials);
        }
    }


    /**
     * @param mixed $credentials
     */
    public function setCredentials($credentials) {
        $this->storageClient = new StorageClient([
            'keyFile' => $credentials
        ]);
    }


    /**
     * Set storage client (useful for testing)
     *
     * @param StorageClient $storageClient
     * @return void
     */
    public function setStorageClient($storageClient){
        $this->storageClient = $storageClient;
    }


    /**
     * Save an object in the passed bucket using key and content directly
     *
     * @param string $bucketKey
     * @param string $objectKey
     * @param string $objectContent
     *
     */
    public function saveObject($bucketKey, $objectKey, $objectContent) {
        $this->storageClient->bucket($bucketKey)->upload($objectContent, [
            "name" => $objectKey
        ]);
    }


    /**
     * Get an object from the passed bucket or throw ItemNotFound
     *
     * @param string $bucketKey
     * @param string $objectKey
     * @return string
     * @throws ItemNotFoundException
     */
    public function getObject($bucketKey, $objectKey) {
        try {
            return $this->storageClient->bucket($bucketKey)->object($objectKey)->downloadAsString();
        } catch (NotFoundException $e) {
            throw new ItemNotFoundException("Object $objectKey does not exist in Google Cloud bucket $bucketKey");
        }
    }


    /**
     * Confirm whether or not an object exists with a boolean response
     *
     * @param $bucketKey
     * @param $objectKey
     *
     * @return boolean
     */
    public function objectExists($bucketKey, $objectKey) {
        return $this->storageClient->bucket($bucketKey)->object($objectKey)->exists();
    }


}