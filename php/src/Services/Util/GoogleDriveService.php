<?php

namespace Kinintel\Services\Util;

use Exception;
use Google\Client;
use Google\Service\Drive;
use GuzzleHttp\Psr7\Response;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\ValueObjects\Authentication\Google\GoogleCloudCredentials;

class GoogleDriveService {
    private Client $client;
    private Drive $drive;

    public function __construct(private AuthenticationCredentialsService $credentialsService) {
        $credentialsKey = Configuration::readParameter("google.drive.credentials.key");
        if (!$credentialsKey) {
            Logger::log("No credentials found for Google Drive");
        } else {
            /**
             * @var GoogleCloudCredentials $credentials
             */
            $credentials = $this->credentialsService->getCredentialsInstanceByKey($credentialsKey)->returnCredentials();
            $credentials = json_decode($credentials->getJsonString(), true);
            if (!$credentials) throw new Exception("Bad Google Drive Credentials");
            $this->client = new Client(["credentials" => $credentials]);
            $this->client->addScope(Drive::DRIVE);
            $this->drive = new Drive($this->client);
        }
    }

    public function setCredentials(string $credentials) {
        echo "\n\n\n";
        echo $credentials;
        $credentials = json_decode($credentials, true);
        if (!$credentials) throw new Exception("Bad Google Drive Credentials");
        $this->client = new Client(["credentials" => $credentials]);
        $this->client->addScope(Drive::DRIVE);
        $this->drive = new Drive($this->client);
    }

    /**
     * @param string $id
     * @return Response
     * @throws Exception
     */
    public function downloadFile(string $id) {
        if (!isset($this->drive)) throw new Exception("Credentials for GoogleDriveService not set");
        $response = $this->drive->files->get($id, ['alt' => 'media']);
        $contents = $response->getBody()->getContents();
        $filetype = $response->getHeaders()["Content-Type"][0];
        $filesize = $response->getHeaders()["Content-Length"][0];
        return [$contents, $filetype, $filesize];
    }
}