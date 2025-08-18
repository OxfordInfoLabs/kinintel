<?php

namespace Kinintel\Objects\Datasource\Document;


use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Services\Attachment\AttachmentService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\Database\BulkData\BulkDataManager;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Exception\SQLException;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\Database\MetaData\TableColumn;
use Kinikit\Persistence\Database\MetaData\TableMetaData;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseUpdatableDatasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Datasource\Document\CustomDocumentParser;
use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;
use Kinintel\Services\Util\Analysis\TextAnalysis\PhraseExtractor;
use Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding\TextEmbeddingService;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Document\DocumentDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceInstanceInfo;
use Kinintel\ValueObjects\Datasource\Document\CustomDocumentData;
use Kinintel\ValueObjects\Datasource\UpdatableMappedField;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterLogic;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\Phrase;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\StopWord;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\TextChunk;

include_once "autoloader.php";

class DocumentDatasourceTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $authCredentials;

    /**
     * @var MockObject
     */
    private $validator;


    /**
     * @var MockObject
     */
    private $databaseConnection;


    /**
     * @var MockObject
     */
    private $bulkDataManager;


    /**
     * @var MockObject
     */
    private $tableDDLGenerator;

    private $mockEmbeddingService;
    private $originalEmbeddingService;
    private $mockDatasourceService;
    private $originalDatasourceService;

    // Setup
    public function setUp(): void {


        $this->databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $this->bulkDataManager = MockObjectProvider::instance()->getMockInstance(BulkDataManager::class);
        $this->databaseConnection->returnValue("getBulkDataManager", $this->bulkDataManager);

        $this->authCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $this->authCredentials->returnValue("returnDatabaseConnection", $this->databaseConnection);

        $this->tableDDLGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $this->validator = MockObjectProvider::instance()->getMockInstance(Validator::class);

        $this->originalEmbeddingService = Container::instance()->get(TextEmbeddingService::class);
        $this->mockEmbeddingService = MockObjectProvider::instance()->getMockInstance(TextEmbeddingService::class);
        Container::instance()->set(TextEmbeddingService::class, $this->mockEmbeddingService);

        $this->originalDatasourceService = Container::instance()->get(DatasourceService::class);
        $this->mockDatasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        Container::instance()->set(DatasourceService::class, $this->mockDatasourceService);
    }

    public function tearDown(): void {
        Container::instance()->set(TextEmbeddingService::class, $this->originalEmbeddingService);
        Container::instance()->set(DatasourceService::class, $this->originalDatasourceService);
    }


    public function testOnInstanceSaveForNewDatasourceInstanceTableCreatedCorrectlyForCoreFields() {

        $sqlDatabaseDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data"),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $this->databaseConnection->throwException("getTableMetaData", new SQLException("Table not found"), ["test_data"]);

        // Call the instance save function
        $sqlDatabaseDatasource->onInstanceSave();

        $newMetaData = new TableMetaData("test_data", [
            new TableColumn("filename", "VARCHAR", 255, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR", 255),
        ]);


        $this->assertTrue($this->tableDDLGenerator->methodWasCalled("generateTableCreateSQL", [
            $newMetaData,
            $this->databaseConnection
        ]));


    }

    public function testOriginalLinkAndContentTextAreStoredWhenSaved() {
        $sqlDatabaseDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data", true, true),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $this->databaseConnection->throwException("getTableMetaData", new SQLException("Table not found"), ["test_data"]);

        // Call the instance save function
        $sqlDatabaseDatasource->onInstanceSave();

        $newMetaData = new TableMetaData("test_data", [
            new TableColumn("filename", "VARCHAR", 255, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR", 255),
            new TableColumn("original_link", "VARCHAR", 255),
            new TableColumn("original_text", "LONGBLOB"),
        ]);


        $this->assertTrue($this->tableDDLGenerator->methodWasCalled("generateTableCreateSQL", [
            $newMetaData,
            $this->databaseConnection
        ]));

    }

    public function testOnInstanceSaveForUpdatedConfigurationModifyIsPerformed() {
        $sqlDatabaseDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data", true, true),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $existingMetaData = new TableMetaData("test_data", [
            new TableColumn("filename", "VARCHAR", 255, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR", 255),
        ]);

        $this->databaseConnection->returnValue("getTableMetaData", $existingMetaData, ["test_data"]);


        // Call the instance save function
        $sqlDatabaseDatasource->onInstanceSave();


        $newMetaData = new TableMetaData("test_data", [
            new TableColumn("filename", "VARCHAR", 255, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR", 255),
            new TableColumn("original_link", "VARCHAR", 255),
            new TableColumn("original_text", "LONGBLOB"),
        ]);


        $this->assertTrue($this->tableDDLGenerator->methodWasCalled("generateTableModifySQL", [
            $existingMetaData,
            $newMetaData,
            $this->databaseConnection
        ]));
    }


    public function testOnUpdateWithDefaultOptionsDatasetIsMappedUpdatedCorrectly() {
        $documentDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data"),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $updateConfig = $documentDatasource->getUpdateConfig();
        $this->assertEquals(0, sizeof($updateConfig->getMappedFields()));

        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentSource"), new Field("file_type")], [["filename" => "test.txt", "documentSource" => "hello test", "file_type" => "application/text"]]);

        $documentDatasource->update($dataset);
        $this->assertTrue($this->bulkDataManager->methodWasCalled("insert", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 10,
                "file_type" => "application/text"
            ]
        ], ["filename", "imported_date", "file_size", "file_type"], true]));

    }

    public function testCanSupplyDocumentFileInsteadOfSource() {

        $documentDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data"),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentFilePath")], [["filename" => "test.txt", "documentFilePath" => __DIR__ . "/test.txt"]]);

        $documentDatasource->update($dataset);
        $this->assertTrue($this->bulkDataManager->methodWasCalled("insert", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 12,
                "file_type" => "text/plain"
            ]
        ], ["filename", "imported_date", "file_size", "file_type"], true]));

    }

    public function testIfStoreTextSuppliedInConfigTextVersionIsPopulatedCorrectly() {
        $documentDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data", false, true),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentFilePath")], [["filename" => "test.txt", "documentFilePath" => __DIR__ . "/test.txt"]]);

        $mockExtractor = MockObjectProvider::instance()->getMockInstance(DocumentTextExtractor::class);
        Container::instance()->set(get_class($mockExtractor), $mockExtractor);
        Container::instance()->addInterfaceImplementation(DocumentTextExtractor::class, "text/plain", get_class($mockExtractor));
        $mockExtractor->returnValue("extractTextFromFile", "Hello World", [__DIR__ . "/test.txt"]);

        $documentDatasource->update($dataset);
        $this->assertTrue($this->bulkDataManager->methodWasCalled("insert", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 12,
                "file_type" => "text/plain",
                "original_text" => "Hello World"
            ]
        ], ["filename", "imported_date", "file_size", "file_type", "original_text"], true]));


        // Now do a string test

        $documentDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data", false, true),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentSource")], [["filename" => "test.txt", "documentSource" => "Hello World"]]);

        $mockExtractor->returnValue("extractTextFromString", "Hello World", ["Hello World"]);

        $documentDatasource->update($dataset);
        $this->assertTrue($this->bulkDataManager->methodWasCalled("insert", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 12,
                "file_type" => "text/plain",
                "original_text" => "Hello World"
            ]
        ], ["filename", "imported_date", "file_size", "file_type", "original_text"], true]));
    }


    public function testIfStoreOriginalSetOriginalDocumentIsStoredUsingConfiguredAttachmentStorage() {

        $mockAttachmentService = MockObjectProvider::instance()->getMockInstance(AttachmentService::class);
        Container::instance()->set(AttachmentService::class, $mockAttachmentService);

        $documentDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data", true, true),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $datasourceInstance = new DatasourceInstance("test_data", "Test Data", "test");
        $datasourceInstance->setAccountId(5);
        $datasourceInstance->setProjectKey("myproject");
        $documentDatasource->setInstanceInfo($datasourceInstance);

        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentSource"), new Field("file_type")], [["filename" => "test.txt", "documentSource" => "Hello World", "file_type" => "text/plain"]]);

        $mockAttachmentService->returnValue("saveAttachment", 25, [
            new AttachmentSummary("test.txt", "text/plain", "DocumentDatasourceInstance", "test_data", "test", "myproject", 5),
            new ReadOnlyStringStream("Hello World")]);

        $documentDatasource->update($dataset);


        $this->assertTrue($this->bulkDataManager->methodWasCalled("insert", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 11,
                "file_type" => "text/plain",
                "original_text" => "Hello World",
                "original_link" => "http://kinicart.test/attachment/25"
            ]
        ], ["filename", "imported_date", "file_size", "file_type", "original_text", "original_link"], true]));

        // Check attachment service was called with original text
        $this->assertTrue($mockAttachmentService->methodWasCalled("saveAttachment", [
            new AttachmentSummary("test.txt", "text/plain", "DocumentDatasourceInstance", "test_data", "test", "myproject", 5),
            new ReadOnlyStringStream("Hello World")]));


    }


    public function testIfIndexWordsSetWithDefaultOptionsUpdateConfigurationIsDefinedCorrectlyAndDatasetContainsPhrases() {
        $previousDatasourceService = Container::instance()->get(DatasourceService::class);

        $mockDatasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        Container::instance()->set(DatasourceService::class, $mockDatasourceService);

        $mockIndexDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockIndexDatasourceInstance, [
            "index_test_data"
        ]);

        $mockIndexDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockIndexDatasourceInstance->returnValue("returnDataSource", $mockIndexDatasource);

        // Mock some existing data for replace checking
        $mockIndexDatasource->returnValue("applyTransformation", $mockIndexDatasource, [
            new FilterTransformation([],
                [
                    new FilterJunction([
                        new Filter("[[document_file_name]]", "test.txt", FilterType::eq)
                    ], [], FilterLogic::AND)
                ],
                FilterLogic::OR
            )
        ]);

        $existingDataset = new ArrayTabularDataset([
            new Field("document_file_name"),
            new Field("phrase"),
            new Field("frequency")
        ], [
            [
                "document_file_name" => "test.txt",
                "phrase" => "Foo",
                "frequency" => 1
            ],
            [
                "document_file_name" => "test.txt",
                "phrase" => "Bar",
                "frequency" => 1
            ]

        ]);
        $mockIndexDatasource->returnValue("materialise", $existingDataset);

        $documentDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data", false, false, true),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $documentDatasource->setInstanceInfo(new DatasourceInstance("test_data", "Test Data", "test"));

        $updateConfig = $documentDatasource->getUpdateConfig();
        $this->assertEquals(1, sizeof($updateConfig->getMappedFields()));
        $mappedField = $updateConfig->getMappedFields()[0];
        $this->assertEquals("phrases", $mappedField->getFieldName());
        $this->assertEquals("index_test_data", $mappedField->getDatasourceInstanceKey());
        $this->assertEquals(["filename" => "document_file_name"], $mappedField->getParentFieldMappings());

        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentFilePath")], [["filename" => "test.txt", "documentFilePath" => __DIR__ . "/test.txt"]]);

        $mockExtractor = MockObjectProvider::instance()->getMockInstance(DocumentTextExtractor::class);
        Container::instance()->set(get_class($mockExtractor), $mockExtractor);
        Container::instance()->addInterfaceImplementation(DocumentTextExtractor::class, "text/plain", get_class($mockExtractor));
        $mockExtractor->returnValue("extractTextFromFile", "Hello World", [__DIR__ . "/test.txt"]);

        $mockPhraseExtractor = MockObjectProvider::instance()->getMockInstance(PhraseExtractor::class);
        Container::instance()->set(PhraseExtractor::class, $mockPhraseExtractor);


        $mockPhraseExtractor->returnValue("extractPhrases", [new Phrase("Hello", 1, 1), new Phrase("World", 2, 1)],
            ["Hello World", 1, 1, [], 'EN']);

        $documentDatasource->update($dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);


        $this->assertTrue($this->bulkDataManager->methodWasCalled("replace", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 12,
                "file_type" => "text/plain",
                "phrases" => [
                    [
                        "section" => "Main",
                        "phrase" => "Hello",
                        "frequency" => 1,
                        "phrase_length" => 1
                    ],
                    [
                        "section" => "Main",
                        "phrase" => "World",
                        "frequency" => 2,
                        "phrase_length" => 1
                    ]
                ]
            ]
        ], ["filename", "imported_date", "file_size", "file_type"]]));


        // Check existing data removed
        $this->assertTrue($mockIndexDatasource->methodWasCalled("update", [
            $existingDataset, UpdatableDatasource::UPDATE_MODE_DELETE
        ]));

        // Check replace operation was made
        $this->assertTrue($mockIndexDatasource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("section"),
                new Field("phrase"),
                new Field("frequency"),
                new Field("phrase_length"),
                new Field("document_file_name")
            ], [
                [
                    "section" => "Main",
                    "phrase" => "Hello",
                    "frequency" => 1,
                    "phrase_length" => 1,
                    "document_file_name" => "test.txt"
                ],
                [
                    "section" => "Main",
                    "phrase" => "World",
                    "frequency" => 2,
                    "phrase_length" => 1,
                    "document_file_name" => "test.txt"
                ]

            ]),
            UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

        Container::instance()->set(DatasourceService::class, $previousDatasourceService);
    }


    public function testIfCustomStopwordsConfigSuppliedCustomStopwordsAreLoadedFromDatasourceColumn() {

        $previousDatasourceService = Container::instance()->get(DatasourceService::class);
        // Set up mock objects for subordinate datasources
        $mockDatasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        Container::instance()->set(DatasourceService::class, $mockDatasourceService);

        $mockIndexDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockIndexDatasourceInstance, [
            "index_test_data"
        ]);

        $mockIndexDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockIndexDatasourceInstance->returnValue("returnDataSource", $mockIndexDatasource);

        // Ensure existing data is resolved
        $mockIndexDatasource->returnValue("applyTransformation", $mockIndexDatasource);

        $mockStopwordsDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockStopwordsDatasourceInstance, [
            "stopwords_ds"
        ]);

        $mockStopwordsDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockStopwordsDatasourceInstance->returnValue("returnDataSource", $mockStopwordsDatasource);

        $mockStopwordsDatasource->returnValue("materialise",
            new ArrayTabularDataset([
                new Field("id"),
                new Field("word")
            ], [
                [
                    "id" => 1,
                    "word" => "a"
                ],
                [
                    "id" => 2,
                    "word" => "the"
                ]
            ]));


        $documentDatasource = new DocumentDatasource(
            new DocumentDatasourceConfig("test_data", false, false, true, [new StopWord(true), new StopWord(false, true, null, null, 3, ["a", "the"])], 1, 1),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $documentDatasource->setInstanceInfo(new DatasourceInstance("test_data", "Test Data", "test"));

        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentFilePath")], [["filename" => "test.txt", "documentFilePath" => __DIR__ . "/test.txt"]]);

        $originalExtractor = Container::instance()->getInterfaceImplementation(DocumentTextExtractor::class, "text/plain");
        $mockExtractor = MockObjectProvider::instance()->getMockInstance(DocumentTextExtractor::class);
        Container::instance()->set(get_class($mockExtractor), $mockExtractor);
        Container::instance()->addInterfaceImplementation(DocumentTextExtractor::class, "text/plain", get_class($mockExtractor));
        $mockExtractor->returnValue("extractTextFromFile", "Hello World", [__DIR__ . "/test.txt"]);

        $mockPhraseExtractor = MockObjectProvider::instance()->getMockInstance(PhraseExtractor::class);
        Container::instance()->set(PhraseExtractor::class, $mockPhraseExtractor);


        $mockPhraseExtractor->returnValue("extractPhrases", [new Phrase("Hello", 1, 1), new Phrase("World", 2, 1)],
            ["Hello World", 1, 1, [new StopWord(true), new StopWord(false, true, null, null, 3, ["a", "the"])], 'EN']);

        $documentDatasource->update($dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);

        $this->assertTrue($this->bulkDataManager->methodWasCalled("replace", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 12,
                "file_type" => "text/plain",
                "phrases" => [
                    [
                        "section" => "Main",
                        "phrase" => "Hello",
                        "frequency" => 1,
                        "phrase_length" => 1,
                    ],
                    [
                        "section" => "Main",
                        "phrase" => "World",
                        "frequency" => 2,
                        "phrase_length" => 1,
                    ]
                ]
            ]
        ], ["filename", "imported_date", "file_size", "file_type"]]));

        Container::instance()->addInterfaceImplementation(DocumentTextExtractor::class, "text/plain", $originalExtractor);
        Container::instance()->set(DatasourceService::class, $previousDatasourceService);
    }


    public function testIfCustomDocumentParserConfiguredForDatasourceAdditionalFieldsAreMergedOnInstanceSave() {

        $mockDocumentParser = MockObjectProvider::instance()->getMockInstance(CustomDocumentParser::class);
        Container::instance()->addInterfaceImplementation(CustomDocumentParser::class, "test", get_class($mockDocumentParser));
        Container::instance()->set(get_class($mockDocumentParser), $mockDocumentParser);

        $mockDocumentParser->returnValue("getAdditionalDocumentFields", [
            new Field("extra1"),
            new Field("extra2")
        ]);

        $documentDatasource = new DocumentDatasource(
            new DocumentDatasourceConfig("test_data", false, false, true, [new StopWord(true), new StopWord(false, true, null, null, 3, ["a", "the"])], 1, 1, "test"),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $existingMetaData = new TableMetaData("test_data", [
            new TableColumn("filename", "VARCHAR", 255, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR", 255),
        ]);

        $this->databaseConnection->returnValue("getTableMetaData", $existingMetaData, ["test_data"]);


        // Call the instance save function
        $documentDatasource->onInstanceSave();


        $newMetaData = new TableMetaData("test_data", [
            new TableColumn("filename", "VARCHAR", 255, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR", 255),
            new TableColumn("extra1", "VARCHAR", 255),
            new TableColumn("extra2", "VARCHAR", 255),
        ]);


        $this->assertTrue($this->tableDDLGenerator->methodWasCalled("generateTableModifySQL", [
            $existingMetaData,
            $newMetaData,
            $this->databaseConnection
        ]));


    }


    public function testIfCustomDocumentParserConfiguredForDataSourceParseDocumentMethodIsCalledOnParserInsteadOfStandardIndexAndDataMergedIntoRow() {

        $previousDatasourceService = Container::instance()->get(DatasourceService::class);
        // Set up mock objects for subordinate datasources
        $mockDatasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        Container::instance()->set(DatasourceService::class, $mockDatasourceService);

        $mockIndexDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockIndexDatasourceInstance, [
            "index_test_data"
        ]);

        $mockIndexDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockIndexDatasourceInstance->returnValue("returnDataSource", $mockIndexDatasource);

        // Ensure existing data is resolved
        $mockIndexDatasource->returnValue("applyTransformation", $mockIndexDatasource);


        $mockDocumentParser = MockObjectProvider::instance()->getMockInstance(CustomDocumentParser::class);
        Container::instance()->addInterfaceImplementation(CustomDocumentParser::class, "test", get_class($mockDocumentParser));
        Container::instance()->set(get_class($mockDocumentParser), $mockDocumentParser);

        $mockDocumentParser->returnValue("getAdditionalDocumentFields", [
            new Field("extra1"),
            new Field("extra2")
        ]);

        $documentDatasourceConfig = new DocumentDatasourceConfig("test_data", false, false, false, [], 1, 3, "test");

        $documentDatasource = new DocumentDatasource($documentDatasourceConfig,
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $documentDatasource->setInstanceInfo(new DatasourceInstance("test_data", "Test Data", "test"));

        $mockDocumentParser->returnValue("parseDocument", new CustomDocumentData([
            "extra1" => "BINGO",
            "extra2" => "BONGO"
        ], [
            "Summary" => [
                new Phrase("hello world", 3, 2),
                new Phrase("hello", 3, 1),
                new Phrase("world", 3, 1)
            ],
            "Main" => [
                new Phrase("our world", 3, 2),
                new Phrase("our", 3, 1),
                new Phrase("world", 3, 1)
            ]
        ]), [
            $documentDatasourceConfig, $documentDatasource->getInstanceInfo(), "hello test", null
        ]);


        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentSource"), new Field("file_type")], [["filename" => "test.txt", "documentSource" => "hello test", "file_type" => "application/text"]]);


        $documentDatasource->update($dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);


        $this->assertTrue($this->bulkDataManager->methodWasCalled("replace", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 10,
                "file_type" => "application/text",
                "extra1" => "BINGO",
                "extra2" => "BONGO",
                "phrases" => [
                    [
                        "section" => "Summary",
                        "phrase" => "hello world",
                        "frequency" => 3,
                        "phrase_length" => 2
                    ],
                    [
                        "section" => "Summary",
                        "phrase" => "hello",
                        "frequency" => 3,
                        "phrase_length" => 1
                    ],
                    [
                        "section" => "Summary",
                        "phrase" => "world",
                        "frequency" => 3,
                        "phrase_length" => 1
                    ],
                    [
                        "section" => "Main",
                        "phrase" => "our world",
                        "frequency" => 3,
                        "phrase_length" => 2
                    ],
                    [
                        "section" => "Main",
                        "phrase" => "our",
                        "frequency" => 3,
                        "phrase_length" => 1
                    ],
                    [
                        "section" => "Main",
                        "phrase" => "world",
                        "frequency" => 3,
                        "phrase_length" => 1
                    ]
                ]
            ]
        ], ["filename", "imported_date", "file_size", "file_type", "extra1", "extra2"]]));


        Container::instance()->set(DatasourceService::class, $previousDatasourceService);

    }


    public function testIfCustomParserConfiguredForDataSourceUpdatableMappedFieldsAreMergedIntoCoreUpdateConfig() {

        $mockDocumentParser = MockObjectProvider::instance()->getMockInstance(CustomDocumentParser::class);
        Container::instance()->addInterfaceImplementation(CustomDocumentParser::class, "test", get_class($mockDocumentParser));
        Container::instance()->set(get_class($mockDocumentParser), $mockDocumentParser);

        $mockDocumentParser->returnValue("getAdditionalDocumentUpdatableMappedFields", [
            new UpdatableMappedField("child1", "test_child"),
            new UpdatableMappedField("child2", "test_child2")
        ]);

        $documentDatasource = new DocumentDatasource(
            new DocumentDatasourceConfig("test_data", false, false, true, [new StopWord(true), new StopWord(false, true, null, null, 3, ["a", "the"])], 1, 1, "test"),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $documentDatasource->setInstanceInfo(new DatasourceInstance("test_data", "Test Data", "test"));

        $this->assertEquals(3, sizeof($documentDatasource->getUpdateConfig()->getMappedFields()));


    }

    public function testOnInstanceDeleteWithoutIndexContentSetMainDataSourceIsDroppedAlongWithIndexAndChunks() {

        $sqlDatabaseDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data", true, true),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $this->tableDDLGenerator->returnValue("generateTableDropSQL", "DROP TABLE test_data", ["test_data"]);

        $originalDatasourceService = Container::instance()->get(DatasourceService::class);
        $mockDatasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        Container::instance()->set(DatasourceService::class, $mockDatasourceService);

        $sqlDatabaseDatasource->setInstanceInfo(new DatasourceInstance("test_data", "Test Data", "test"));

        // Do instance delete
        $sqlDatabaseDatasource->onInstanceDelete();

        // Check main instance deleted
        $this->assertTrue($this->databaseConnection->methodWasCalled("executeScript", ["DROP TABLE test_data"]));

        // Check index table deleted
        $this->assertTrue($mockDatasourceService->methodWasCalled("removeDatasourceInstance", [
            "index_test_data"
        ]));
        $this->assertTrue($mockDatasourceService->methodWasCalled("removeDatasourceInstance", [
            "chunks_test_data"
        ]));

        Container::instance()->set(DatasourceService::class, $originalDatasourceService);

    }

    public function testIfIndexTableDeleteFailsNoExceptionIsRaised() {


        $sqlDatabaseDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data", true, true),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $this->tableDDLGenerator->returnValue("generateTableDropSQL", "DROP TABLE test_data", ["test_data"]);

        $originalDatasourceService = Container::instance()->get(DatasourceService::class);
        $mockDatasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        Container::instance()->set(DatasourceService::class, $mockDatasourceService);

        $sqlDatabaseDatasource->setInstanceInfo(new DatasourceInstance("test_data", "Test Data", "test"));

        $mockDatasourceService->throwException("removeDatasourceInstance", new SQLException("Bad Table"), ["index_test_data"]);

        // Do instance delete
        $sqlDatabaseDatasource->onInstanceDelete();

        // Check main instance deleted
        $this->assertTrue($this->databaseConnection->methodWasCalled("executeScript", ["DROP TABLE test_data"]));

        // Check index table deleted
        $this->assertTrue($mockDatasourceService->methodWasCalled("removeDatasourceInstance", [
            "index_test_data"
        ]));

        Container::instance()->set(DatasourceService::class, $originalDatasourceService);
    }


    public function testIfCustomParserConfiguredForDatasourceOnInstanceDeleteIsCalledOnCustomParserWhenOnInstanceDeleteCalledOnDatasourceToEnsureResourcesCleanedUpEffectively() { // ?

        $mockDocumentParser = MockObjectProvider::instance()->getMockInstance(CustomDocumentParser::class);
        Container::instance()->addInterfaceImplementation(CustomDocumentParser::class, "test", get_class($mockDocumentParser));
        Container::instance()->set(get_class($mockDocumentParser), $mockDocumentParser);


        $config = new DocumentDatasourceConfig("test_data", false, false, true, [new StopWord(true), new StopWord(false, true, null, null, 3, ["a", "the"])], 1, 1, "test");

        $documentDatasource = new DocumentDatasource(
            $config,
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $instance = new DatasourceInstance("test", "Test", "test");
        $documentDatasource->setInstanceInfo($instance);


        // Call on instance delete
        $documentDatasource->onInstanceDelete();

        // Check method called on parser
        $this->assertTrue($mockDocumentParser->methodWasCalled("onDocumentDatasourceDelete", [
            $config, new DatasourceInstanceInfo($instance)
        ]));

    }

    public function testChunkContent() {
        $config = new DocumentDatasourceConfig(
            "test_data",
            false,
            false,
            false,
            false,
            chunkContent: true, indexChunksByAI: false);

        $inputText = "hello test\n\nit's me";

        $mockExtractor = MockObjectProvider::instance()->getMockInstance(DocumentTextExtractor::class);
        Container::instance()->set(get_class($mockExtractor), $mockExtractor);
        Container::instance()->addInterfaceImplementation(DocumentTextExtractor::class, "text/plain", get_class($mockExtractor));
        $mockExtractor->returnValue("extractChunksFromString", [
            new TextChunk("hello test", 0, 11),
            new TextChunk("it's me", 12, 7)
        ], [$inputText]
        );

        $documentDatasource = new DocumentDatasource(
            $config,
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $instance = new DatasourceInstance("testChunks", "Test Chunks", "test");
        $documentDatasource->setInstanceInfo($instance);

        $dataset = new ArrayTabularDataset([
            new Field("filename"),
            new Field("documentSource"),
            new Field("file_type")
        ], [[
            "filename" => "test.txt",
            "documentSource" => $inputText,
            "file_type" => "text/plain"
        ]]);

        // Mock out datasource service for the chunk datasource
        $mockChunkDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $this->mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockChunkDatasourceInstance, [
            "chunks_testChunks"
        ]);
        $mockChunkDatasource = MockObjectProvider::instance()->getMockInstance(BaseUpdatableDatasource::class);
        $mockChunkDatasourceInstance->returnValue("returnDataSource", $mockChunkDatasource);
        $mockChunkDatasource->returnValue("applyTransformation", $mockChunkDatasource);

        //CALL THE METHOD
        $documentDatasource->update($dataset);

        $this->assertTrue($mockChunkDatasource->methodWasCalled("update"));
        $hist = $mockChunkDatasource->getMethodCallHistory("update");
        /** @var ArrayTabularDataset $chunkUpdateCall */
        $chunkUpdateCall =  $hist[0][0];

        $columnNames = array_map(fn($col) => $col->getName() ,$chunkUpdateCall->getColumns());
        $this->assertTrue(in_array("chunk_text", $columnNames));
        $this->assertTrue(in_array("chunk_number", $columnNames));
        $this->assertTrue(in_array("chunk_pointer", $columnNames));
        $this->assertTrue(in_array("chunk_length", $columnNames));


        $chunkData = $chunkUpdateCall->getAllData();
        $this->assertEquals(2, count($chunkData));
        $this->assertEquals($chunkData[0]["chunk_text"], "hello test");

        $insertHistory = $this->bulkDataManager->getMethodCallHistory("insert");
        $lastInsertCall = $insertHistory[0];
        $this->assertEquals("hello test", $lastInsertCall[1][0]["chunks"][0]["chunk_text"]);
        $this->assertEquals("it's me", $lastInsertCall[1][0]["chunks"][1]["chunk_text"]);
        //Chunk number starts at zero
        $this->assertEquals(0, $lastInsertCall[1][0]["chunks"][0]["chunk_number"]);
    }

    public function testTurnChunksToEmbeddingsBasic(){
        $input = [
            new TextChunk("abcdef", 0, 6),
            new TextChunk("ghijklm", 7, 7),
            new TextChunk("nopqrstuv", 15, 9),
        ];
        $this->mockEmbeddingService->returnValue("embedStrings", [[0, 1], [1, 0], [0.5, 0.5]], [["abcdef", "ghijklm", "nopqrstuv"]]);

        $output = DocumentDatasource::turnChunksToEmbeddings($input);

        $hist = $this->mockEmbeddingService->getMethodCallHistory("embedStrings");
        $this->assertEquals([["abcdef", "ghijklm", "nopqrstuv"]], $hist[0]);

        $this->assertEquals(3, count($output));
        $this->assertEquals("abcdef", $output[0]["chunk_text"]);
        $this->assertEquals("nopqrstuv", $output[2]["chunk_text"]);

        $this->assertEquals(15, $output[2]["chunk_pointer"]);
        $this->assertEquals(9, $output[2]["chunk_length"]);

        $this->assertEquals("[0.5,0.5]", $output[2]["embedding"]);
    }

    public function testTurnChunksToEmbeddingsSplitsChunksCorrectly(){
        $input = [
            new TextChunk("abcdefghijklmnopqrstuvwxyz", 0, 26),
            new TextChunk("four five", 26, 9),
            new TextChunk("four vier", 35, 9)
        ];
        $this->mockEmbeddingService->returnValue("embedStrings", [[0, 1]], [["abcdefghijklmno"]]);
        $this->mockEmbeddingService->returnValue("embedStrings", [[1, 0], [0.5, 0.5]], [["pqrstuvwxyz", "four five"]]);
        $this->mockEmbeddingService->returnValue("embedStrings", [[-0.5, -0.5]], [["four vier"]]);

        $output = DocumentDatasource::turnChunksToEmbeddings($input, 20, 15);

        $hist = $this->mockEmbeddingService->getMethodCallHistory("embedStrings");
        $this->assertEquals([["abcdefghijklmno"]], $hist[0]);

        $this->assertEquals(4, count($output));
        $this->assertEquals("abcdefghijklmno", $output[0]["chunk_text"]);
        $this->assertEquals("pqrstuvwxyz", $output[1]["chunk_text"]);
        $this->assertEquals("four five", $output[2]["chunk_text"]);
        $this->assertEquals("four vier", $output[3]["chunk_text"]);

        $this->assertEquals(15, $output[1]["chunk_pointer"]);
        $this->assertEquals(11, $output[1]["chunk_length"]);

        $this->assertEquals("[0.5,0.5]", $output[2]["embedding"]);
    }

    public function testSplitChunk(){
        $text = "SHORT CHUNK";
        $testChunk = new TextChunk($text, 0, strlen($text));
        $chunks = DocumentDatasource::splitChunk($testChunk, 20);
        $this->assertEquals(1, count($chunks));
        $this->assertEquals("SHORT CHUNK", $chunks[0]->getText());

        $text = "HELLO! It's me Peter and I'm going to be split up!";
        $testChunk = new TextChunk($text, 0, strlen($text));
        $chunks = DocumentDatasource::splitChunk($testChunk, 20);
        $this->assertEquals(3, count($chunks));
        $this->assertEquals("HELLO! It's me Peter", $chunks[0]->getText());
        $this->assertEquals(" split up!", $chunks[2]->getText());
        $this->assertEquals(40, $chunks[2]->getPointer());
        $this->assertEquals(10, $chunks[2]->getLength());
    }

    public function testTurnChunksToEmbeddingsSplitsRequestsUp(){
        $input = [
            new TextChunk("a really long piece of text.", 0, 28),
            new TextChunk("a bit more", 29, 10),
        ];
//        $this->mockEmbeddingService->returnValue("embedStrings", [[0, 1], [1, 0], [0.5, 0.5]]);
        $this->mockEmbeddingService->returnValue("embedStrings", [[0, 1]], [["a really long piece of text."]]);
        $this->mockEmbeddingService->returnValue("embedStrings", [[1, 0]], [["a bit more"]]);

        $output = DocumentDatasource::turnChunksToEmbeddings($input, 30);

        $hist = $this->mockEmbeddingService->getMethodCallHistory("embedStrings");
        $this->assertEquals(2, count($hist));

        $this->assertEquals(2, count($output));
        $this->assertEquals("a bit more", $output[1]["chunk_text"]);

        $this->assertEquals(28, $output[0]["chunk_length"]);
        $this->assertEquals(29, $output[1]["chunk_pointer"]);
        $this->assertEquals(10, $output[1]["chunk_length"]);

        $this->assertEquals("[0,1]", $output[0]["embedding"]);
        $this->assertEquals("[1,0]", $output[1]["embedding"]);
    }

    public function testIndexByAI(){
        $config = new DocumentDatasourceConfig(
            "test_data",
            false,
            false,
            false,
            false,
            chunkContent: true, indexChunksByAI: true);

        $mockExtractor = MockObjectProvider::instance()->getMockInstance(DocumentTextExtractor::class);
        Container::instance()->set(get_class($mockExtractor), $mockExtractor);
        Container::instance()->addInterfaceImplementation(DocumentTextExtractor::class, "text/plain", get_class($mockExtractor));
        $mockExtractor->returnValue("extractChunksFromString", [
            new TextChunk("hello test", 0, 11)
        ], ["hello test"]
        );

        $documentDatasource = new DocumentDatasource(
            $config,
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);

        $instance = new DatasourceInstance("testAI", "Test AI", "test");
        $documentDatasource->setInstanceInfo($instance);

        $dataset = new ArrayTabularDataset([
            new Field("filename"),
            new Field("documentSource"),
            new Field("file_type")
        ], [[
            "filename" => "test.txt",
            "documentSource" => "hello test",
            "file_type" => "text/plain"
        ]]);

        $this->mockEmbeddingService->returnValue("embedStrings", [[0.5]], [["hello test"]]);

        // Mock out datasource service for the chunk datasource
        $mockChunkDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $this->mockDatasourceService->returnValue("getDataSourceInstanceByKey", $mockChunkDatasourceInstance, [
            "chunks_testAI"
        ]);
        $mockChunkDatasource = MockObjectProvider::instance()->getMockInstance(BaseUpdatableDatasource::class);
        $mockChunkDatasourceInstance->returnValue("returnDataSource", $mockChunkDatasource);
        $mockChunkDatasource->returnValue("applyTransformation", $mockChunkDatasource);

        //CALL THE METHOD
        $documentDatasource->update($dataset);

        $this->assertTrue($mockChunkDatasource->methodWasCalled("update"));
        $hist = $mockChunkDatasource->getMethodCallHistory("update");
        /** @var ArrayTabularDataset $chunkUpdateCall */
        $chunkUpdateCall =  $hist[0][0];

        $columnNames = array_map(fn($col) => $col->getName() ,$chunkUpdateCall->getColumns());
        $this->assertTrue(in_array("chunk_text", $columnNames));
        $this->assertTrue(in_array("chunk_number", $columnNames));
        $this->assertTrue(in_array("chunk_pointer", $columnNames));
        $this->assertTrue(in_array("chunk_length", $columnNames));
        $this->assertTrue(in_array("embedding", $columnNames));

        $chunkData = $chunkUpdateCall->getAllData();
        $this->assertEquals("[0.5]", $chunkData[0]["embedding"]);

        $insertHistory = $this->bulkDataManager->getMethodCallHistory("insert");
        $lastInsertCall = $insertHistory[0];
        $this->assertTrue($this->mockEmbeddingService->methodWasCalled("embedStrings", [["hello test"]]));
        $this->assertEquals("hello test", $lastInsertCall[1][0]["chunks"][0]["chunk_text"]);
        //Chunk number starts at zero
        $this->assertEquals(0, $lastInsertCall[1][0]["chunks"][0]["chunk_number"]);
        $this->assertEquals("[0.5]", $lastInsertCall[1][0]["chunks"][0]["embedding"]);
    }
}
