<?php

namespace Kinintel\Test\Objects\Datasource\WebScraper;

use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\WebScraper\WebScraperDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\WebScraper\FieldWithXPathSelector;
use Kinintel\ValueObjects\Datasource\Configuration\WebScraper\WebScraperDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class WebScraperDatasourceTest extends TestCase {


    /**
     * @var MockObject
     */
    private $httpDispatcher;

    public function setUp(): void {
        $this->httpDispatcher = MockObjectProvider::instance()->getMockInstance(HttpRequestDispatcher::class);
    }


    public function testCanMaterialiseSimpleDivValueWebScrapingStructureAndConvertToDataset() {

        $expectedResponse = new Response(new ReadOnlyFileStream(__DIR__ . "/test.html"), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [], null, new Headers([Headers::USER_AGENT => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)"])));

        $webScraperDatasource = new WebScraperDatasource(new WebScraperDatasourceConfig("https://mytest.com", "//div[@id='grid']//div[@class='row']", 0, [
            new FieldWithXPathSelector("name", "div[@class='name']", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("age", "div[@class='age']", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("category", "div[@class='category']", FieldWithXPathSelector::ATTRIBUTE_TEXT)
        ]));

        $webScraperDatasource->setHttpRequestDispatcher($this->httpDispatcher);

        $dataset = $webScraperDatasource->materialiseDataset();
        $this->assertEquals(new ArrayTabularDataset([
            new Field("name"),
            new Field("age"),
            new Field("category")
        ], [
            ["name" => "Mark", "age" => 33, "category" => "Tech"],
            ["name" => "Dave", "age" => 44, "category" => "HR"],
            ["name" => "Emma", "age" => 22, "category" => "Admin"]
        ]), $dataset);


    }


    public function testCanMaterialiseSimpleDivAttributeBasedWebScrapingStructureAndConvertToDataset() {

        $expectedResponse = new Response(new ReadOnlyFileStream(__DIR__ . "/test.html"), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [], null, new Headers([Headers::USER_AGENT => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)"])));

        $webScraperDatasource = new WebScraperDatasource(new WebScraperDatasourceConfig("https://mytest.com", "//div[@class='rows-compressed']/div", 0, [
            new FieldWithXPathSelector("name", ".", "data-name"),
            new FieldWithXPathSelector("age", ".", "data-age"),
            new FieldWithXPathSelector("position", "input", "value")
        ]));

        $webScraperDatasource->setHttpRequestDispatcher($this->httpDispatcher);

        $dataset = $webScraperDatasource->materialiseDataset();
        $this->assertEquals(new ArrayTabularDataset([
            new Field("name"),
            new Field("age"),
            new Field("position")
        ], [
            ["name" => "Mary", "age" => 35, "position" => "Director"],
            ["name" => "Joe", "age" => 18, "position" => "Intern"],
        ]), $dataset);


    }


    public function testCanMaterialiseTabularBasedWebScrapingStructureAndConvertToDataset() {

        $expectedResponse = new Response(new ReadOnlyFileStream(__DIR__ . "/test.html"), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [], null, new Headers([Headers::USER_AGENT => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)"])));

        $webScraperDatasource = new WebScraperDatasource(new WebScraperDatasourceConfig("https://mytest.com", "//table/tr", 0, [
            new FieldWithXPathSelector("name", "td[1]", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("age", "td[2]", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("hobby", "td[3]/span", FieldWithXPathSelector::ATTRIBUTE_HTML)
        ]));

        $webScraperDatasource->setHttpRequestDispatcher($this->httpDispatcher);

        $dataset = $webScraperDatasource->materialiseDataset();
        $this->assertEquals(new ArrayTabularDataset([
            new Field("name"),
            new Field("age"),
            new Field("hobby")
        ], [
            ["name" => "John", "age" => 44, "hobby" => "<span>Diving</span>"],
            ["name" => "James", "age" => 55, "hobby" => "<span>Piano Player</span>"],
            ["name" => "Ingrid", "age" => 66, "hobby" => "<span>Shopping</span>"]
        ]), $dataset);


    }

    public function testDoesAcknowledgeFirstRowOffset() {

        $expectedResponse = new Response(new ReadOnlyFileStream(__DIR__ . "/test2.html"), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [], null, new Headers([Headers::USER_AGENT => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)"])));

        $webScraperDatasource = new WebScraperDatasource(new WebScraperDatasourceConfig("https://mytest.com", "//table//table/tr", 1, [
            new FieldWithXPathSelector("id", "td[1]", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("logged_at", "td[2]", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("not_before", "td[3]/span", FieldWithXPathSelector::ATTRIBUTE_TEXT)
        ]));

        $webScraperDatasource->setHttpRequestDispatcher($this->httpDispatcher);

        $dataset = $webScraperDatasource->materialiseDataset();

        $this->assertEquals(new ArrayTabularDataset([
            new Field("id"),
            new Field("logged_at"),
            new Field("not_before")
        ], [
            ["id" => "12312038140", "logged_at" => "2024-03-08"],
            ["id" => "12312067716", "logged_at" => "2024-03-08"],
            ["id" => "12188526615", "logged_at" => "2024-02-25"]
        ]), $dataset);
    }

    public function testIfPagingTransformationPassedAsTransformationItIsAppliedToReturnedResults(){

        $expectedResponse = new Response(new ReadOnlyFileStream(__DIR__ . "/test.html"), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [], null, new Headers([Headers::USER_AGENT => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)"])));

        $webScraperDatasource = new WebScraperDatasource(new WebScraperDatasourceConfig("https://mytest.com", "//div[@id='grid']//div[@class='row']", 0, [
            new FieldWithXPathSelector("name", "div[@class='name']", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("age", "div[@class='age']", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("category", "div[@class='category']", FieldWithXPathSelector::ATTRIBUTE_TEXT)
        ]));

        $webScraperDatasource->setHttpRequestDispatcher($this->httpDispatcher);

        $webScraperDatasource->applyTransformation(new PagingTransformation(2, 0));

        $dataset = $webScraperDatasource->materialiseDataset();
        $this->assertEquals(new ArrayTabularDataset([
            new Field("name"),
            new Field("age"),
            new Field("category")
        ], [
            ["name" => "Mark", "age" => 33, "category" => "Tech"],
            ["name" => "Dave", "age" => 44, "category" => "HR"]
        ]), $dataset);



        $expectedResponse = new Response(new ReadOnlyFileStream(__DIR__ . "/test.html"), 200, null, null);

        $this->httpDispatcher->returnValue("dispatch", $expectedResponse,
            new Request("https://mytest.com", Request::METHOD_GET, [], null, new Headers([Headers::USER_AGENT => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)"])));


        $webScraperDatasource = new WebScraperDatasource(new WebScraperDatasourceConfig("https://mytest.com", "//div[@id='grid']//div[@class='row']", 0, [
            new FieldWithXPathSelector("name", "div[@class='name']", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("age", "div[@class='age']", FieldWithXPathSelector::ATTRIBUTE_TEXT),
            new FieldWithXPathSelector("category", "div[@class='category']", FieldWithXPathSelector::ATTRIBUTE_TEXT)
        ]));

        $webScraperDatasource->setHttpRequestDispatcher($this->httpDispatcher);

        $webScraperDatasource->applyTransformation(new PagingTransformation(3, 1));

        $dataset = $webScraperDatasource->materialiseDataset();
        $this->assertEquals(new ArrayTabularDataset([
            new Field("name"),
            new Field("age"),
            new Field("category")
        ], [
            ["name" => "Dave", "age" => 44, "category" => "HR"],
            ["name" => "Emma", "age" => 22, "category" => "Admin"]
        ]), $dataset);

    }

}