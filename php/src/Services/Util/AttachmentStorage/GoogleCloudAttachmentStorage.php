<?php


namespace Kinintel\Services\Util\AttachmentStorage;


use Kinintel\Services\Util\GoogleCloudStorageService;
use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Services\Attachment\AttachmentStorage;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\MissingConfigurationParameterException;
use Kinikit\Core\Stream\ReadableStream;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;

class GoogleCloudAttachmentStorage extends AttachmentStorage {

    /**
     * @var GoogleCloudStorageService
     */
    private $googleCloudStorageService;

    /**
     * @var string
     */
    private $bucketName;


    /**
     * GoogleCloudAttachmentStorage constructor.
     *
     * @param AuthenticationCredentialsService $authenticationCredentialsService
     */
    public function __construct($authenticationCredentialsService) {

        $credentialsKey = Configuration::readParameter("google.cloud.attachment.storage.credentials.key");

        if (!$credentialsKey) {
            throw new MissingConfigurationParameterException("google.cloud.attachment.storage.credentials.key");
        }

        $this->bucketName = Configuration::readParameter("google.cloud.attachment.storage.bucket");

        if (!$this->bucketName) {
            throw new MissingConfigurationParameterException("google.cloud.attachment.storage.bucket");
        }

        $matchingCredentialsInstance = $authenticationCredentialsService->getCredentialsInstanceByKey($credentialsKey);
        $matchingCredentials = $matchingCredentialsInstance->returnCredentials();
        $jsonCredentials = json_decode($matchingCredentials->getJsonString(), true);
        $this->googleCloudStorageService = new GoogleCloudStorageService($jsonCredentials);


    }

    /**
     * Setter for service for testing purposes
     *
     * @param GoogleCloudStorageService $googleCloudStorageService
     */
    public function setGoogleCloudStorageService(GoogleCloudStorageService $googleCloudStorageService): void {
        $this->googleCloudStorageService = $googleCloudStorageService;
    }


    /**
     * Save attachment content
     *
     * @param AttachmentSummary $attachment
     * @param ReadableStream $contentStream
     * @return mixed|void
     */
    public function saveAttachmentContent($attachment, $contentStream) {
        $this->googleCloudStorageService->saveObject($this->bucketName, $this->generatePath($attachment), $contentStream->getContents());
    }


    /**
     * Remove attachment content
     *
     * @param AttachmentSummary $attachment
     * @return mixed|void
     */
    public function removeAttachmentContent($attachment) {
        // TODO: Implement removeAttachmentContent() method.
    }

    /**
     * Get attachment content
     *
     * @param AttachmentSummary $attachment
     * @return string|void
     */
    public function getAttachmentContent($attachment) {
        return $this->googleCloudStorageService->getObject($this->bucketName, $this->generatePath($attachment));
    }


    /**
     * @param AttachmentSummary $attachment
     */
    private function generatePath($attachment) {
        $path = $attachment->getAccountId() ? "account/" . $attachment->getAccountId() : "";
        if ($attachment->getProjectKey()) $path .= "/" . $attachment->getProjectKey();
        $path .= "/" . $attachment->getParentObjectType() . "/" . $attachment->getParentObjectId() . "/" . $attachment->getId() . "-" . $attachment->getAttachmentFilename();
        return $path;
    }

}
