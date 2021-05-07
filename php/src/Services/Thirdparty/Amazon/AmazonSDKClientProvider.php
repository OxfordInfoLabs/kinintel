<?php


namespace Kinintel\Services\Thirdparty\Amazon;

use Aws\S3\S3Client;

/**
 * Provider for Amazon SDK Clients - improves testability
 * of SDK
 *
 * Class AmazonSDKClientProvider
 * @package Kinintel\Services\Thirdparty\Amazon
 */
class AmazonSDKClientProvider {

    /**
     * Create an S3 client for a region access key and secret
     *
     * @return S3Client
     */
    public function createS3Client($region, $accessKey, $secret) {

        return new S3Client([
            'version' => 'latest',
            'signatureVersion' => 'v4',
            'region' => $region,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secret
            ]
        ]);
    }

}