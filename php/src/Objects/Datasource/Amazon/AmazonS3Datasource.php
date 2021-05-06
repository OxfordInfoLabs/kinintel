<?php

namespace Kinintel\Objects\Datasource\Amazon;

use Aws\S3\S3Client;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\ValueObjects\Authentication\Generic\AccessKeyAndSecretAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Datasource\Configuration\Amazon\AmazonS3DatasourceConfig;
use Kinintel\ValueObjects\Transformation\Transformation;

class AmazonS3Datasource extends Datasource {

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
     * @return Datasource|void
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

            $s3 = new S3Client([
                'version' => 'latest',
                'signatureVersion' => 'v4',
                'region' => $config->getRegion(),
                'credentials' => [
                    'key' => $credentials->getAccessKey(),
                    'secret' => $credentials->getSecret()
                ]
            ]);

            $result = $s3->getObject([
                'Bucket' => $config->getBucket(),
                'Key' => $config->getFilename()
            ]);

            print_r($result);

        }


    }
}