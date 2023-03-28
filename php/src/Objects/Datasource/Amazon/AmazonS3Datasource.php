<?php

namespace Kinintel\Objects\Datasource\Amazon;

use GuzzleHttp\Psr7\Stream;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Thirdparty\Amazon\AmazonSDKClientProvider;
use Kinintel\ValueObjects\Authentication\Generic\AccessKeyAndSecretAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Datasource\Configuration\Amazon\AmazonS3DatasourceConfig;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class AmazonS3Datasource extends BaseDatasource {

    /**
     * @var PagingTransformation[]
     */
    private $pagingTransformations = [];

    public function getConfigClass() {
        return AmazonS3DatasourceConfig::class;
    }

    public function getSupportedCredentialClasses() {
        return [AccessKeyAndSecretAuthenticationCredentials::class];
    }

    /**
     * We don't support any transformation classes directly here
     *
     * @return array
     */
    public function getSupportedTransformationClasses() {
        return [PagingTransformation::class];
    }


    /**
     * Apply any transformation to the results of this datasource.
     *
     * @param Transformation $transformation
     * @param array $parameterValues
     * @param null $pagingTransformation
     * @return BaseDatasource|void
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {

        // Track paging transformations
        if ($transformation instanceof PagingTransformation)
            $this->pagingTransformations[] = $transformation;

        return $this;
    }

    /**
     * Materialise a dataset for this datasource.
     *
     * @param array $parameterValues
     * @return Dataset|void
     */
    public function materialiseDataset($parameterValues = []) {

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

                $offset = 0;
                $limit = PHP_INT_MAX;

                // Increment the offset and limit accordingly.
                foreach ($this->pagingTransformations as $pagingTransformation) {
                    $offset += $pagingTransformation->getOffset();
                    $limit = $pagingTransformation->getLimit();
                }

                // Temporary read all contents logic
                $body = $body->getContents();
                return $config->returnFormatter()->format(new ReadOnlyStringStream($body), $config->returnEvaluatedColumns($parameterValues), $limit, $offset);
            }

        }


    }


}