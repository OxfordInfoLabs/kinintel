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
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\Util\TextAnalysis\DocumentTextExtractor;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Document\DocumentDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;

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
}
