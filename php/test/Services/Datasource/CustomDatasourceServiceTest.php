<?php

namespace Kinintel\Test\Services\Datasource;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\CustomDatasourceService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Util\Analysis\TextAnalysis\Extractors\DocxTextExtractor;
use Kinintel\Services\Util\Analysis\TextAnalysis\Extractors\PDFTextExtractor;
use Kinintel\Services\Util\GoogleDriveService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\TabularResultsDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceConfigUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

include_once "autoloader.php";

class CustomDatasourceServiceTest extends TestBase {

    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var CustomDatasourceService
     */
    private $customDatasourceService;

    /**
     * @var GoogleDriveService
     */
    private $googleDriveService;

    /**
     * @return void
     */
    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);

        $this->googleDriveService = MockObjectProvider::instance()->getMockInstance(GoogleDriveService::class);
        $this->customDatasourceService = new CustomDatasourceService(
            $this->datasourceService,
            Container::instance()->get(HttpRequestDispatcher::class),
            $this->googleDriveService
        );
    }

    public function testCanCreateCustomDatasourceUsingUpdateWithStructureObject() {


        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", null, [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $mockInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDatasourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);
        $mockInstance->returnValue("returnDataSource", $mockDatasource);
        $mockDatasource->returnValue("getConfig", $mockDatasourceConfig);
        $this->datasourceService->returnValue("saveDataSourceInstance", $mockInstance);

        $newDatasourceKey = $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, null, "myproject", 1);

        $expectedDatasourceInstance = new DatasourceInstance($newDatasourceKey, "Hello world", "custom", [
            "source" => "table",
            "tableName" => "custom." . $newDatasourceKey,
            "columns" => [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
        ], "test");
        $expectedDatasourceInstance->setAccountId(1);
        $expectedDatasourceInstance->setProjectKey("myproject");

        // Check datasource was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $expectedDatasourceInstance
        ]));


        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", [
            $newDatasourceKey, $datasourceUpdate
        ]));


    }

    public function testIfDatasourceKeySuppliedItIsUsedOnCreateCustomDatasourceUsingUpdateWithStructureObject() {


        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", null, [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $mockInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDatasourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);
        $mockInstance->returnValue("returnDataSource", $mockDatasource);
        $mockDatasource->returnValue("getConfig", $mockDatasourceConfig);
        $this->datasourceService->returnValue("saveDataSourceInstance", $mockInstance);

        $newDatasourceKey = $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, "bingbango", "myproject", 1);

        $this->assertEquals("bingbango", $newDatasourceKey);

        $expectedDatasourceInstance = new DatasourceInstance($newDatasourceKey, "Hello world", "custom", [
            "source" => "table",
            "tableName" => "custom.bingbango",
            "columns" => [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
        ], "test");
        $expectedDatasourceInstance->setAccountId(1);
        $expectedDatasourceInstance->setProjectKey("myproject");

        // Check datasource was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $expectedDatasourceInstance
        ]));


        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", [
            $newDatasourceKey, $datasourceUpdate
        ]));


    }


    public function testIfCustomDataSourceCreationFailsInstanceIsDeleted() {

        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", null, [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $mockInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDatasourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);
        $mockInstance->returnValue("returnDataSource", $mockDatasource);
        $mockDatasource->returnValue("getConfig", $mockDatasourceConfig);
        $this->datasourceService->throwException("saveDataSourceInstance", new \Exception("RANDOM FAILURE"));

        try {
            $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, null, "myproject", 1);
            $this->fail("Should have thrown here");
        } catch (\Exception $e) {
            $this->assertEquals("RANDOM FAILURE", $e->getMessage());
            $this->assertTrue($this->datasourceService->methodWasCalled("removeDatasourceInstance", [
                "custom_data_set_1_" . date("U")
            ]));
        }


    }

    public function testCanCreateDocumentDatasourceInstanceAndIndexDatasourceInstanceAndChunkDatasourceInstance() {
        $indexFields = [
            new Field("document_file_name", "Document File Name", null, Field::TYPE_STRING, true),
            new Field("section", "Section", null, Field::TYPE_STRING, true),
            new Field("phrase", "Phrase", null, Field::TYPE_STRING, true),
            new Field("phrase_length", "Phrase Length", null, Field::TYPE_INTEGER),
            new Field("frequency", "Frequency", null, Field::TYPE_INTEGER)
        ];

        $chunkFields = [
            new Field("document_file_name", "Document File Name", null, Field::TYPE_STRING, true),
            new Field("chunk_text", "Chunk Text", null, Field::TYPE_LONG_STRING),
            new Field("chunk_number", "Chunk Number", null, Field::TYPE_INTEGER, true),
            new Field("chunk_pointer", "Chunk Pointer", null, Field::TYPE_INTEGER),
            new Field("chunk_length", "Chunk Length", null, Field::TYPE_INTEGER),
            new Field("embedding", "Embedding", null, Field::TYPE_MEDIUM_STRING)
        ];

        $documentDatasourceConfig = ["tableName" => Configuration::readParameter("custom.datasource.table.prefix") . "document_data_set_4_" . date("U"),
            "storeOriginal" => true, "storeText" => true, "indexContent" => true];
        $documentIndexDatasourceConfig = ["tableName" => Configuration::readParameter("custom.datasource.table.prefix") . "index_document_data_set_4_" . date("U"),
            "source" => "table", "columns" => $indexFields, "manageTableStructure" => true];
        $documentChunksDatasourceConfig = ["tableName" => Configuration::readParameter("custom.datasource.table.prefix") . "chunks_document_data_set_4_" . date("U"),
            "source" => "table", "columns" => $chunkFields, "manageTableStructure" => true];

        $expectedInstance = new DatasourceInstance("document_data_set_4_" . date("U"), "TheBestTitle", "document",
            $documentDatasourceConfig, Configuration::readParameter("custom.datasource.credentials.key"));
        $expectedIndexInstance = new DatasourceInstance("index_document_data_set_4_" . date("U"), "TheBestTitle Index",
            "sqldatabase", $documentIndexDatasourceConfig, Configuration::readParameter("custom.datasource.credentials.key"));
        $expectedChunksInstance = new DatasourceInstance("chunks_document_data_set_4_" . date("U"), "TheBestTitle Chunks",
            "sqldatabase", $documentChunksDatasourceConfig, Configuration::readParameter("custom.datasource.credentials.key"));


        $expectedInstance->setAccountId(4);
        $expectedInstance->setProjectKey("theBestKey");

        $expectedIndexInstance->setAccountId(4);
        $expectedIndexInstance->setProjectKey("theBestKey");

        $expectedChunksInstance->setAccountId(4);
        $expectedChunksInstance->setProjectKey("theBestKey");

        $updateConfig = new DatasourceConfigUpdate("TheBestTitle", $documentDatasourceConfig);

        $expectedInstanceKey = $this->customDatasourceService->createDocumentDatasourceInstance($updateConfig, "theBestKey", 4);

        $this->assertEquals($expectedInstance, $this->datasourceService->getMethodCallHistory("saveDataSourceInstance")[0][0]);
        $this->assertEquals($expectedIndexInstance, $this->datasourceService->getMethodCallHistory("saveDataSourceInstance")[1][0]);
        $this->assertEquals($expectedChunksInstance, $this->datasourceService->getMethodCallHistory("saveDataSourceInstance")[2][0]);

        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [$expectedInstance]));
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [$expectedIndexInstance]));
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [$expectedChunksInstance]));
        $this->assertEquals("document_data_set_4_" . date("U"), $expectedInstanceKey);
    }

    public function testCanCreateTabularSnapshotDatasourceInstance() {

        $returnInstance = $this->customDatasourceService->createTabularSnapshotDatasourceInstance("My Test Instance",
            [new Field("Field1"), new Field("Field2")], "dummydummy", 53
        );

        $this->assertEquals("snapshot_data_set_53_" . date("U"), $returnInstance->getKey());

        $expectedInstance = new DatasourceInstance("snapshot_data_set_53_" . date("U"), "My Test Instance",
            "snapshot", [
                "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                "tableName" => "snapshot_data_set_53_" . date("U"),
                "columns" => [new Field("Field1"), new Field("Field2")]
            ], "test");
        $expectedInstance->setProjectKey("dummydummy");
        $expectedInstance->setAccountId(53);


        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $expectedInstance
        ]));

        $this->assertEquals($expectedInstance, $returnInstance);

    }

    public function testCanUploadDocumentsToCustomDatasourceByUrl(){
        $links = [
            "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf",
            "https://freetestdata.com/wp-content/uploads/2023/03/Sample_HTML_for_testing.html",
            "https://calibre-ebook.com/downloads/demos/demo.docx",
            "https://drive.google.com/file/d/1slIH6T0vq0RoPJW5QSEU7VwsqSCNt5Iv/view?usp=share_link",
            "https://drive.google.com/uc?export=download&id=1Dr3vL2yLVIsMVVzsTVtXnkYv5-MECzaY"
        ];
        $dsInstanceKey = "test_url_upload_key";

        $mockDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $mockDatasourceInstance->returnValue("returnDataSource", $mockDatasource);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDatasourceInstance, [$dsInstanceKey]);

        $this->googleDriveService->returnValue("downloadFile", ["This is some file contents", "text/html", 100], ["1slIH6T0vq0RoPJW5QSEU7VwsqSCNt5Iv"]);
        $this->googleDriveService->returnValue("downloadFile", ["This is some other file contents", "text/html", 100], ["1Dr3vL2yLVIsMVVzsTVtXnkYv5-MECzaY"]);

        $this->customDatasourceService->uploadDocumentsFromUrl($dsInstanceKey, $links);

        //Check that the datasource was updated
        $this->assertTrue($mockDatasource->methodWasCalled("update"));
        $callHistory = $mockDatasource->getMethodCallHistory("update");
        $this->assertEquals(count($links), count($callHistory));

        //W3 example pdf
        $w3pdf = $callHistory[0][0]->nextRawDataItem();
        /**
         * @var PDFTextExtractor $pdfParser
         */
        $pdfParser = Container::instance()->get(PDFTextExtractor::class);
        $this->assertTrue(str_contains($pdfParser->extractTextFromString($w3pdf["documentSource"]), "Dummy PDF file"));
        $this->assertEquals("application/pdf", $w3pdf["file_type"]);

        //Free test data html
        $testHtml = $callHistory[1][0]->nextRawDataItem();
        $this->assertTrue(str_contains($testHtml["documentSource"], "Sample HTML File"));
        $this->assertTrue(str_contains($testHtml["documentSource"], "Lorem ipsum"));
        $this->assertEquals("text/html", $testHtml["file_type"]);

        //FileSamples docx
        $testDocx = $callHistory[2][0]->nextRawDataItem();
        $docxParser = Container::instance()->get(DocxTextExtractor::class);
        $this->assertTrue(str_contains($docxParser->extractTextFromString($testDocx["documentSource"]), "Demonstration of DOCX support in calibre"));
        $this->assertEquals("application/vnd.openxmlformats-officedocument.wordprocessingml.document", $testDocx["file_type"]);

        //Google Drive .pdf
//        $testGdriveShare = $callHistory[3][0]->nextRawDataItem();
//        $this->assertTrue(str_contains($pdfParser->extractTextFromString($testGdriveShare["documentSource"]),
//            "This is the seventh volume of the annual Internet Governance Forum"));
//        $this->assertEquals("application/pdf", $testGdriveShare["file_type"]);

        //Google Drive .pdf from export link
//        $testGdriveExport = $callHistory[4][0]->nextRawDataItem();
//        $this->assertTrue(str_contains($pdfParser->extractTextFromString($testGdriveExport["documentSource"]),
//            "So far, sixteen annual meetings of the IGF"));
//        $this->assertEquals("application/pdf", $testGdriveExport["file_type"]);

        //Google Drive .pdf
        $testGdriveExport = $callHistory[3][0]->nextRawDataItem();
        $this->assertTrue(str_contains($testGdriveExport["documentSource"],
            "some file contents"));
        $this->assertEquals("text/html", $testGdriveExport["file_type"]);

        //Google Drive .pdf from export link
        $testGdriveExport = $callHistory[4][0]->nextRawDataItem();
        $this->assertTrue(str_contains($testGdriveExport["documentSource"],
            "some other file contents"));
        $this->assertEquals("text/html", $testGdriveExport["file_type"]);
    }
}
