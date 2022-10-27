<?php

namespace Kinintel\Objects\Datasource\Google;

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use GuzzleHttp\Psr7\Stream;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\ReadOnlyMultiStream;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Authentication\Google\GoogleCloudCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\Google\GoogleBucketFileDatasourceConfig;

class GoogleBucketFileDatasource extends BaseDatasource {

    public function getConfigClass() {
        return GoogleBucketFileDatasourceConfig::class;
    }

    public function getSupportedCredentialClasses()  {
        return [GoogleCloudCredentials::class];
    }

    public function materialiseDataset($parameterValues = []) {

        /**
         * @var GoogleBucketFileDatasourceConfig $config
         */
        $config = $this->getConfig();

        /**
         * @var GoogleCloudCredentials $credentials
         */
        $credentials = $this->getAuthenticationCredentials();

        /**
         * @var ParameterisedStringEvaluator $parameterisedStringEvaluator
         */
        $parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);

        if ($config) {

            $googleClient = new StorageClient(["keyFile" => json_decode($credentials->getJsonString(), true)]);

            $bucket = $googleClient->bucket($config->getBucket());

            $folder = $parameterisedStringEvaluator->evaluateString($config->getFolder(), [], []);
            $config->setFolder($folder);

            $filePath = $parameterisedStringEvaluator->evaluateString($config->getFilePath(), [], []);
            $config->setFilePath($filePath);

            if ($filePath) {
                $object = $bucket->object($filePath);

                $stream = new ReadOnlyGuzzleStream($object->downloadAsStream());

                $limit = PHP_INT_MAX;
                $offset = 0;
                return $config->returnFormatter()->format($stream, $config->returnEvaluatedColumns($parameterValues), $limit, $offset);
            }

            if ($folder) {
                $streams = [];

                foreach ($bucket->objects(["prefix" => $folder]) as $object) {
                    if ($object->info()["size"] > 0) {
                        $streams[] = new ReadOnlyGuzzleStream($object->downloadAsStream());
                    }
                }

                $multiStream = new ReadOnlyMultiStream($streams);

                $limit = PHP_INT_MAX;
                $offset = 0;

                return $config->returnFormatter()->format($multiStream, $config->returnEvaluatedColumns($parameterValues), $limit, $offset);

            }

        }


    }

    public function getSupportedTransformationClasses() {
        // TODO: Implement getSupportedTransformationClasses() method.
    }

    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {
        // TODO: Implement applyTransformation() method.
    }
}