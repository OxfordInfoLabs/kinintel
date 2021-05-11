<?php

namespace Kinintel\Objects\Datasource\WebService;

use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers as ReqHeaders;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\QueryParameterAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
use Kinintel\ValueObjects\Transformation\Query\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Query\FilterQuery;

include_once "autoloader.php";

class WebServiceDatasourceTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $httpDispatcher;

    public function setUp(): void {
        $this->httpDispatcher = MockObjectProvider::instance()->getMockInstance(HttpRequestDispatcher::class);
    }


    public function testCanMaterialiseSimpleUnfilteredDatasourceForGetRequest() {

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


    public function testCanMaterialiseFilteredGetRequestDatasource() {

        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Pinger"}'), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [
                "name" => "Bobby smith",
                "scope" => "local"
            ]));

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com"));

        $request->applyTransformation(new FilterQuery([
            new Filter("name", "Bobby smith"),
            new Filter("scope", "local"),
        ]));

        $request->setDispatcher($this->httpDispatcher);


        // Materialise
        $response = $request->materialiseDataset();

        // Check that the response was received directly
        $this->assertEquals(new ArrayTabularDataset([new Field("value", "Value")], [
            [
                "value" => "Pinger"
            ]
        ]), $response);
    }


    public function testCanMaterialiseFilteredGetRequestDataSourceWithBasicAuthentication() {

        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Pinky"}'), 200, null, null);

        $headers = new ReqHeaders();
        $headers->set(ReqHeaders::AUTHORISATION, "Basic " . base64_encode('baggy:trousers'));

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [
                "name" => "Bobby smith",
                "scope" => "local"
            ], null, $headers));

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com", Request::METHOD_GET, [
            "username" => "baggy",
            "password" => "trousers"
        ]), new BasicAuthenticationCredentials("baggy", "trousers"));


        $request->applyTransformation(new FilterQuery([
            new Filter("name", "Bobby smith"),
            new Filter("scope", "local"),
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


    public function testCanMaterialiseFilteredGetRequestDataSourceWithQueryParamAuthentication() {


        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Pinky"}'), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [
                "name" => "Bobby smith",
                "scope" => "local",
                "token" => "baggy",
                "secret" => "trousers"
            ]));

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com", Request::METHOD_GET), new QueryParameterAuthenticationCredentials([
            "token" => "baggy",
            "secret" => "trousers"
        ]));

        $request->applyTransformation(new FilterQuery([
            new Filter("name", "Bobby smith"),
            new Filter("scope", "local"),
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

    public function testCanMaterialiseFilteredPostRequestWithPayloadTemplate() {

        $expectedResponse = new Response(new ReadOnlyStringStream('{"name": "Bosh"}'), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_POST, ["token" => "baggy", "secret" => "trousers"],
                "static:true,name:Bobby smith,scope:local"));


        $template = "static:true,name:{{name}}{{#scope}},scope:{{.}}{{/scope}}{{#optional}},optional:{{.}}{{/optional}}";

        $request = new TestWebServiceDataSource(new WebserviceDataSourceConfig("https://mytest.com", Request::METHOD_POST, [], $template), new QueryParameterAuthenticationCredentials([
            "token" => "baggy",
            "secret" => "trousers"
        ]));

        $request->applyTransformation(new FilterQuery([
            new Filter("name", "Bobby smith"),
            new Filter("scope", "local"),
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

}