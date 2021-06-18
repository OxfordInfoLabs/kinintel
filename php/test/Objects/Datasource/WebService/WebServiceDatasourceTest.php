<?php

namespace Kinintel\Objects\Datasource\WebService;

use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers as ReqHeaders;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\Objects\ResultFormatter\ResultFormatter;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\QueryParameterAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
use Kinintel\ValueObjects\Datasource\Processing\Compression\Configuration\ZipCompressorConfiguration;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;

include_once "autoloader.php";

class WebServiceDatasourceTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $httpDispatcher;

    public function setUp(): void {
        $this->httpDispatcher = MockObjectProvider::instance()->getMockInstance(HttpRequestDispatcher::class);
    }


    public function testCanMaterialiseSimpleDatasourceForGetRequest() {

        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Pingu"}'), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET));

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com"));
        $request->setDispatcher($this->httpDispatcher);

        // Materialise
        $response = $request->materialiseDataset();

        // Check that the response was received directly
        $this->assertEquals(new ArrayTabularDataset([new Field("value", "Value")], [
            [
                "value" => "Pingu"
            ]
        ]), $response);

    }

    public function testCanMaterialiseDatasourceWithCompression() {

        $expectedResponse = new Response(new ReadOnlyFileStream(__DIR__ . "/../../../Services/Datasource/Processing/Compression/test-compressed.zip"), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET));

        $config = new WebserviceDataSourceConfig("https://mytest.com", Request::METHOD_GET, null, "sv");
        $config->setCompressionType("zip");
        $config->setCompressionConfig(new ZipCompressorConfiguration("test.csv"));

        $request = new TestWebServiceDataSource($config);
        $request->setDispatcher($this->httpDispatcher);

        /**
         * @var SVStreamTabularDataSet $response
         */
        $response = $request->materialiseDataset();

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $response);

        $this->assertEquals(["column1" => "Name", "column2" => "Age", "column3" => "Shoe Size"], $response->nextDataItem());
        $this->assertEquals(["column1" => "Mark", "column2" => 50, "column3" => 10], $response->nextDataItem());
        $this->assertEquals(["column1" => "Bob", "column2" => 20, "column3" => 9], $response->nextDataItem());
        $this->assertEquals(["column1" => "Mary", "column2" => 30, "column3" => 7], $response->nextDataItem());


    }


    public function testCanMaterialiseGetRequestDataSourceWithBasicAuthentication() {

        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Pinky"}'), 200, null, null);

        $headers = new ReqHeaders();
        $headers->set(ReqHeaders::AUTHORISATION, "Basic " . base64_encode('baggy:trousers'));

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [], null, $headers));

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com", Request::METHOD_GET, [
            "username" => "baggy",
            "password" => "trousers"
        ]), new BasicAuthenticationCredentials("baggy", "trousers"));


        $request->setDispatcher($this->httpDispatcher);


        // Materialise
        $response = $request->materialiseDataset();

        // Check that the response was received directly
        $this->assertEquals(new ArrayTabularDataset([new Field("value", "Value")], [
            [
                "value" => "Pinky"
            ]
        ]), $response);

    }


    public function testCanMaterialiseGetRequestDataSourceWithQueryParamAuthentication() {


        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Pinky"}'), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [
                "token" => "baggy",
                "secret" => "trousers"
            ]));

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com", Request::METHOD_GET), new QueryParameterAuthenticationCredentials([
            "token" => "baggy",
            "secret" => "trousers"
        ]));


        $request->setDispatcher($this->httpDispatcher);


        // Materialise
        $response = $request->materialiseDataset();

        // Check that the response was received directly
        $this->assertEquals(new ArrayTabularDataset([new Field("value", "Value")], [
            [
                "value" => "Pinky"
            ]
        ]), $response);

    }

    public function testCanMaterialisePostRequestWithPayloadTemplate() {

        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Bosh"}'), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_POST, ["token" => "baggy", "secret" => "trousers"],
                "static:true"));


        $template = "static:true";

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com", Request::METHOD_POST, $template), new QueryParameterAuthenticationCredentials([
            "token" => "baggy",
            "secret" => "trousers"
        ]));


        $request->setDispatcher($this->httpDispatcher);


        // Materialise
        $response = $request->materialiseDataset();

        // Check that the response was received directly
        $this->assertEquals(new ArrayTabularDataset([new Field("value", "Value")], [
            [
                "value" => "Bosh"
            ]
        ]), $response);


    }


    public function testAnyConfiguredColumnsArePassedToFormatterFormatFunction() {

        $expectedResponse = new Response(new ReadOnlyStringStream('[{"name": "Pingu", "age": 33},{"name": "Pooch", "age": 5}]'), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET));

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com", Request::METHOD_GET,
            null, "json", [], [
                new Field("age")
            ]));
        $request->setDispatcher($this->httpDispatcher);

        // Materialise
        $response = $request->materialiseDataset();

        // Check that the response was received directly
        $this->assertEquals(new ArrayTabularDataset([new Field("age", "Age")], [
            [
                "name" => "Pingu",
                "age" => 33
            ],
            [
                "name" => "Pooch",
                "age" => 5
            ]
        ]), $response);

    }


    public function testAnySuppliedDataSourceParameterValuesAreMadeAvailableToBothUrlAndPayload() {

        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Bosh"}'), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com?scope=test", Request::METHOD_POST, ["token" => "baggy", "secret" => "trousers"],
                "staticValue:12345,mode:test"));


        $template = "staticValue:{{staticValue}},mode:{{mode}}";

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com?scope={{scope}}", Request::METHOD_POST, $template), new QueryParameterAuthenticationCredentials([
            "token" => "baggy",
            "secret" => "trousers"
        ]));


        $request->setDispatcher($this->httpDispatcher);


        // Materialise
        $response = $request->materialiseDataset([
            "scope" => "test",
            "staticValue" => 12345,
            "mode" => "test"
        ]);

        // Check that the response was received directly
        $this->assertEquals(new ArrayTabularDataset([new Field("value", "Value")], [
            [
                "value" => "Bosh"
            ]
        ]), $response);

    }


    public function testIfPagingTransformationsAppliedToDatasourceTheyAreStoredAndAppliedToFormatterAtMaterialise() {

        $stream = new ReadOnlyStringStream('[{"name": "Bob"},{"name": "Pete"},{"name": "Rose"}]');
        $expectedResponse = new Response($stream, 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET));

        $config = MockObjectProvider::instance()->getMockInstance(WebserviceDataSourceConfig::class);
        $formatter = MockObjectProvider::instance()->getMockInstance(ResultFormatter::class);
        $config->returnValue("returnFormatter", $formatter);
        $config->returnValue("getUrl", "https://mytest.com");
        $config->returnValue("getMethod", Request::METHOD_GET);


        $validator = MockObjectProvider::instance()->getMockInstance(Validator::class);

        // Check default offset and limit passed when no transformations
        $request = new TestWebServiceDataSource($config, null, $validator);
        $request->setDispatcher($this->httpDispatcher);

        // Materialise
        $request->materialiseDataset();

        $this->assertTrue($formatter->methodWasCalled("format", [
            $stream, [], PHP_INT_MAX, 0]));


        // Check limit passed correctly when supplied
        $request = new TestWebServiceDataSource($config, null, $validator);
        $request->setDispatcher($this->httpDispatcher);

        $request->applyTransformation(new PagingTransformation(100));

        // Materialise
        $request->materialiseDataset();

        $this->assertTrue($formatter->methodWasCalled("format", [
            $stream, [], 100, 0]));


        // Check offset passed correctly when supplied
        $request = new TestWebServiceDataSource($config, null, $validator);
        $request->setDispatcher($this->httpDispatcher);

        $request->applyTransformation(new PagingTransformation(100, 10));

        // Materialise
        $request->materialiseDataset();

        $this->assertTrue($formatter->methodWasCalled("format", [
            $stream, [], 100, 10]));


        // Apply a second transformation
        $request->applyTransformation(new PagingTransformation(20, 20));
        // Materialise
        $request->materialiseDataset();

        $this->assertTrue($formatter->methodWasCalled("format", [
            $stream, [], 20, 30]));


    }


}