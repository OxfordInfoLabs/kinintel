<?php

namespace Kinintel\Objects\Datasource\Amazon;

use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\ResultFormatter\ResultFormatter;
use Kinintel\Services\Thirdparty\Amazon\AmazonSDKClientProvider;
use Kinintel\ValueObjects\Authentication\Generic\AccessKeyAndSecretAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Amazon\AmazonS3DatasourceConfig;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;

include_once "autoloader.php";

class AmazonS3DatasourceTest extends \PHPUnit\Framework\TestCase {


    /**
     * @var MockObject
     */
    private $mockSDK;

    /**
     * @var MockObject
     */
    private $mockClient;

    public function setUp(): void {

        // Set up our mock provider
        $this->mockSDK = MockObjectProvider::instance()->getMockInstance(AmazonSDKClientProvider::class);
        Container::instance()->set(AmazonSDKClientProvider::class, $this->mockSDK);

        $this->mockClient = MockObjectProvider::instance()->getMockInstance(S3Client::class, ["args" => [
            'version' => 'latest',
            'signatureVersion' => 'v4',
            'region' => "eu-west-1",
            'credentials' => [
                'key' => "HELLO",
                'secret' => "BINGO"
            ]
        ]]);


        $this->mockSDK->returnValue("createS3Client", $this->mockClient, [
            "eu-west-1", "Hocus", "Pocus"
        ]);


        $mockGuzzleStream = MockObjectProvider::instance()->getMockInstance(Stream::class);

        $this->mockClient->returnValue("getObject", ["Body" => $mockGuzzleStream], [[
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

    }

    public function testCanAccessAuthenticatedBucketResource() {


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


    public function testCanApplyPagingTransformationsAndTheseArePassedToFormatter() {

        $config = MockObjectProvider::instance()->getMockInstance(AmazonS3DatasourceConfig::class);
        $formatter = MockObjectProvider::instance()->getMockInstance(ResultFormatter::class);
        $config->returnValue("returnFormatter", $formatter);
        $config->returnValue("getBucket", "mytestbucket");
        $config->returnValue("getFilename", "test.json");

        $this->mockSDK->returnValue("createS3Client", $this->mockClient);

        $validator = MockObjectProvider::instance()->getMockInstance(Validator::class);

        // Check limiting one
        $datasource = new AmazonS3Datasource($config,
            new AccessKeyAndSecretAuthenticationCredentials("Hocus", "Pocus"), $validator);


        $datasource->applyTransformation(new PagingTransformation(1));

        $datasource->materialiseDataset();


        $this->assertTrue($formatter->methodWasCalled("format", [
            new ReadOnlyStringStream('[{
           "name": "Mark",
           "age": 25
        },
        {
            "name": "Joe",
            "age": 50
        }
        ]'), [], 1, 0
        ]));


        // Check offset one
        $datasource = new AmazonS3Datasource($config,
            new AccessKeyAndSecretAuthenticationCredentials("Hocus", "Pocus"), $validator);


        $datasource->applyTransformation(new PagingTransformation(1, 1));

        $datasource->materialiseDataset();


        $this->assertTrue($formatter->methodWasCalled("format", [
            new ReadOnlyStringStream('[{
           "name": "Mark",
           "age": 25
        },
        {
            "name": "Joe",
            "age": 50
        }
        ]'), [], 1, 1
        ]));
    }

}