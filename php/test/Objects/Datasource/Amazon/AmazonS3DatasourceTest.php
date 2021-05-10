<?php

namespace Kinintel\Objects\Datasource\Amazon;

use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Services\Thirdparty\Amazon\AmazonSDKClientProvider;
use Kinintel\ValueObjects\Authentication\Generic\AccessKeyAndSecretAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Amazon\AmazonS3DatasourceConfig;

include_once "autoloader.php";

class AmazonS3DatasourceTest extends \PHPUnit\Framework\TestCase {

    public function testCanAccessAuthenticatedBucketResource() {

        // Set up our mock provider
        $mockSDK = MockObjectProvider::instance()->getMockInstance(AmazonSDKClientProvider::class);
        Container::instance()->set(AmazonSDKClientProvider::class, $mockSDK);

        $mockClient = MockObjectProvider::instance()->getMockInstance(S3Client::class, ["args" => [
            'version' => 'latest',
            'signatureVersion' => 'v4',
            'region' => "eu-west-1",
            'credentials' => [
                'key' => "HELLO",
                'secret' => "BINGO"
            ]
        ]]);


        $mockSDK->returnValue("createS3Client", $mockClient, [
            "eu-west-1", "Hocus", "Pocus"
        ]);


        $mockGuzzleStream = MockObjectProvider::instance()->getMockInstance(Stream::class);

        $mockClient->returnValue("getObject", ["Body" => $mockGuzzleStream], [[
            'Bucket' => "mytestbucket",
            'Key' => "test.json"
        ]]);

        $mockGuzzleStream->returnValue("getContents", '[{
           "name": "Mark",
           "age": 25
        },
        {
            "name": "Joe",
            "age": 50
        }
        ]');


        $datasource = new AmazonS3Datasource(new AmazonS3DatasourceConfig("eu-west-1", "mytestbucket", "test.json"),
            new AccessKeyAndSecretAuthenticationCredentials("Hocus", "Pocus"));


        $result = $datasource->materialiseDataset();

        $this->assertEquals([new Field("name", "Name"), new Field("age", "Age")],
            $result->getColumns());

        $this->assertEquals([
            [
                "name" => "Mark",
                "age" => 25
            ],
            [
                "name" => "Joe",
                "age" => 50
            ]
        ], $result->getAllData());

    }

}