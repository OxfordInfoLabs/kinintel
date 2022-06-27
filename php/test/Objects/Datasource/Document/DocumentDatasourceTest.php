<?php

namespace Kinintel\Objects\Datasource\Document;


use Kinikit\Core\DependencyInjection\Container;
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
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Util\TextAnalysis\DocumentTextExtractor;
use Kinintel\Services\Util\TextAnalysis\PhraseExtractor;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Document\DocumentDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Util\TextAnalysis\Phrase;

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


    // Setup
    public function setUp(): void {


        $this->databaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $this->bulkDataManager = MockObjectProvider::instance()->getMockInstance(BulkDataManager::class);
        $this->databaseConnection->returnValue("getBulkDataManager", $this->bulkDataManager);

        $this->authCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $this->authCredentials->returnValue("returnDatabaseConnection", $this->databaseConnection);

        $this->tableDDLGenerator = MockObjectProvider::instance()->getMockInstance(TableDDLGenerator::class);

        $this->validator = MockObjectProvider::instance()->getMockInstance(Validator::class);

    }


    public function testOnInstanceSaveForNewDatasourceInstanceTableCreatedCorrectlyForCoreFields() {

        $sqlDatabaseDatasource = new DocumentDatasource(new DocumentDatasourceConfig("test_data"),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $this->databaseConnection->throwException("getTableMetaData", new SQLException("Table not found"), ["test_data"]);

        // Call the instance save function
        $sqlDatabaseDatasource->onInstanceSave();

        $newMetaData = new TableMetaData("test_data", [
            new TableColumn("filename", "VARCHAR", null, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR"),
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
            new TableColumn("filename", "VARCHAR", null, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR"),
            new TableColumn("original_link", "VARCHAR"),
            new TableColumn("original_text", "BLOB"),
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
            new TableColumn("filename", "VARCHAR", null, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR"),
        ]);

        $this->databaseConnection->returnValue("getTableMetaData", $existingMetaData, ["test_data"]);


        // Call the instance save function
        $sqlDatabaseDatasource->onInstanceSave();


        $newMetaData = new TableMetaData("test_data", [
            new TableColumn("filename", "VARCHAR", null, null, null, true),
            new TableColumn("imported_date", "DATETIME"),
            new TableColumn("file_size", "INTEGER"),
            new TableColumn("file_type", "VARCHAR"),
            new TableColumn("original_link", "VARCHAR"),
            new TableColumn("original_text", "BLOB"),
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
        ], ["filename", "imported_date", "file_size", "file_type"]]));

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
        ], ["filename", "imported_date", "file_size", "file_type"]]));

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
        ], ["filename", "imported_date", "file_size", "file_type", "original_text"]]));


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
        ], ["filename", "imported_date", "file_size", "file_type", "original_text"]]));
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
                        new Filter("[[document_file_name]]", "test.txt", Filter::FILTER_TYPE_EQUALS)
                    ], [], FilterJunction::LOGIC_AND)
                ],
                FilterJunction::LOGIC_OR
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

        $documentDatasource->setInstanceInfo("test_data", "Test Data", []);

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
            ["Hello World", 1, 1, true, [], 'EN']);

        $documentDatasource->update($dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);

        $this->assertTrue($this->bulkDataManager->methodWasCalled("replace", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 12,
                "file_type" => "text/plain",
                "phrases" => [
                    [
                        "phrase" => "Hello",
                        "frequency" => 1,
                        "phrase_length" => 1
                    ],
                    [
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
                new Field("phrase"),
                new Field("frequency"),
                new Field("phrase_length"),
                new Field("document_file_name")
            ], [
                [
                    "phrase" => "Hello",
                    "frequency" => 1,
                    "phrase_length" => 1,
                    "document_file_name" => "test.txt"
                ],
                [
                    "phrase" => "World",
                    "frequency" => 2,
                    "phrase_length" => 1,
                    "document_file_name" => "test.txt"
                ]

            ]),
            UpdatableDatasource::UPDATE_MODE_ADD
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
            new DocumentDatasourceConfig("test_data", false, false, true, true, 1, 1, true, "stopwords_ds", "word"),
            $this->authCredentials, null, $this->validator, $this->tableDDLGenerator);


        $documentDatasource->setInstanceInfo("test_data", "Test Data", []);

        $dataset = new ArrayTabularDataset([new Field("filename"), new Field("documentFilePath")], [["filename" => "test.txt", "documentFilePath" => __DIR__ . "/test.txt"]]);

        $mockExtractor = MockObjectProvider::instance()->getMockInstance(DocumentTextExtractor::class);
        Container::instance()->set(get_class($mockExtractor), $mockExtractor);
        Container::instance()->addInterfaceImplementation(DocumentTextExtractor::class, "text/plain", get_class($mockExtractor));
        $mockExtractor->returnValue("extractTextFromFile", "Hello World", [__DIR__ . "/test.txt"]);

        $mockPhraseExtractor = MockObjectProvider::instance()->getMockInstance(PhraseExtractor::class);
        Container::instance()->set(PhraseExtractor::class, $mockPhraseExtractor);


        $mockPhraseExtractor->returnValue("extractPhrases", [new Phrase("Hello", 1, 1), new Phrase("World", 2, 1)],
            ["Hello World", 1, 1, true, ["a", "the"], 'EN']);

        $documentDatasource->update($dataset, UpdatableDatasource::UPDATE_MODE_REPLACE);

        $this->assertTrue($this->bulkDataManager->methodWasCalled("replace", ["test_data", [
            [
                "filename" => "test.txt",
                "imported_date" => date("Y-m-d H:i:s"),
                "file_size" => 12,
                "file_type" => "text/plain",
                "phrases" => [
                    [
                        "phrase" => "Hello",
                        "frequency" => 1,
                        "phrase_length" => 1,
                    ],
                    [
                        "phrase" => "World",
                        "frequency" => 2,
                        "phrase_length" => 1,
                    ]
                ]
            ]
        ], ["filename", "imported_date", "file_size", "file_type"]]));

        Container::instance()->set(DatasourceService::class, $previousDatasourceService);
    }

}
