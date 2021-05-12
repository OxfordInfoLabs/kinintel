<?php

namespace Kinintel\Objects\Datasource\Amazon;

use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Thirdparty\Amazon\AmazonSDKClientProvider;
use Kinintel\ValueObjects\Authentication\Generic\AccessKeyAndSecretAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Datasource\Configuration\Amazon\AmazonS3DatasourceConfig;
use Kinintel\ValueObjects\Transformation\Transformation;

class AmazonS3Datasource extends BaseDatasource {

    public function getConfigClass() {
        return AmazonS3DatasourceConfig::class;
    }

    public function getSupportedCredentialClasses() {
        return [AccessKeyAndSecretAuthenticationCredentials::class];
    }

    /**
     * Apply any transformation to the results of this datasource.
     *
     * @param Transformation $transformation
     * @return BaseDatasource|void
     */
    public function applyTransformation($transformation) {
        // TODO: Implement applyTransformation() method.
    }

    /**
     * Materialise a dataset for this datasource.
     *
     * @return Dataset|void
     */
    public function materialiseDataset() {

        /**
         * @var AmazonS3DatasourceConfig $config
         */
        $config = $this->getConfig();

        /**
         * @var AccessKeyAndSecretAuthenticationCredentials $credentials
         */
        $credentials = $this->getAuthenticationCredentials();

        if ($config) {

            /**
             * @var AmazonSDKClientProvider $sdkClientProvider
             */
            $sdkClientProvider = Container::instance()->get(AmazonSDKClientProvider::class);

            // Grab S3 from sdk provider
            $s3 = $sdkClientProvider->createS3Client($config->getRegion(), $credentials->getAccessKey(), $credentials->getSecret());

            $result = $s3->getObject([
                'Bucket' => $config->getBucket(),
                'Key' => $config->getFilename()
            ]);

            $body = $result["Body"];

            // If a stream object, read whole stream and convert to tabular data
            if ($body instanceof Stream) {
                $body = $body->getContents();
                return $config->returnFormatter()->format(new ReadOnlyStringStream($body));
            }

        }


    }
}