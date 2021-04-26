<?php

namespace Kinintel\Services\Authentication;

use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;

/**
 * Operate on authentication credentials - in particular manage the hybridity of
 * stored credential instances in both filesystem and database.
 *
 * Class AuthenticationCredentialsService
 */
class AuthenticationCredentialsService {

    /**
     * @var FileResolver
     */
    private $fileResolver;


    /**
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;


    /**
     * Cached file system credentials
     *
     * @var AuthenticationCredentialsInstance[]
     */
    private $fileSystemCredentials = null;

    /**
     * AuthenticationCredentialsService constructor.
     *
     * @param FileResolver $fileResolver
     * @param JSONToObjectConverter $jsonToObjectConverter
     */
    public function __construct($fileResolver, $jsonToObjectConverter) {
        $this->fileResolver = $fileResolver;
        $this->jsonToObjectConverter = $jsonToObjectConverter;
    }


    /**
     * Get a credentials instance by key
     *
     * @param $key
     */
    public function getCredentialsInstanceByKey($key) {

        try {
            return AuthenticationCredentialsInstance::fetch($key);
        } catch (ItemNotFoundException $e) {

            // Ensure we have loaded any built in credentials
            if ($this->fileSystemCredentials === null) {
                $this->loadFileSystemCredentials();
            }

            if (isset($this->fileSystemCredentials[$key])) {
                return $this->fileSystemCredentials[$key];
            }

        }


    }


    /**
     * Save a credentials instance
     *
     * @param AuthenticationCredentialsInstance $credentialsInstance
     */
    public function saveCredentialsInstance($credentialsInstance) {
        $credentialsInstance->save();
    }


    // Load file system credentials
    private function loadFileSystemCredentials() {
        $this->fileSystemCredentials = [];

        $searchPaths = $this->fileResolver->getSearchPaths();
        foreach ($searchPaths as $searchPath) {
            $credentialDir = $searchPath . "/Config/credentials";
            if (file_exists($credentialDir)) {
                $fileCredentials = scandir($credentialDir);
                foreach ($fileCredentials as $fileCredential) {
                    if (strpos($fileCredential, ".json")) {
                        $splitFilename = explode(".", $fileCredential);
                        $instance = $this->jsonToObjectConverter->convert(file_get_contents($credentialDir . "/" . $fileCredential), AuthenticationCredentialsInstance::class);
                        $instance->setKey($splitFilename[0]);
                        $this->fileSystemCredentials[$splitFilename[0]] = $instance;
                    }
                }
            }
        }


    }

}
