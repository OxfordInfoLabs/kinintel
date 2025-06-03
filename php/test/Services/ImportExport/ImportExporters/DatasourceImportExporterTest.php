<?php

namespace Kinintel\Test\Services\ImportExport\ImportExporters;


use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\ImportExport\ImportExporters\DatasourceImportExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\ImportExport\ExportConfig\DatasourceExportConfig;
use Kinintel\ValueObjects\ImportExport\ExportObjects\ExportedDatasource;

include_once "autoloader.php";

class DatasourceImportExporterTest extends TestBase {


    /**
     * @var DatasourceImportExporter
     */
    private $importExporter;


    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::mock(DatasourceService::class);
        $this->dataProcessorService = MockObjectProvider::mock(DataProcessorService::class);
        $this->importExporter = new DatasourceImportExporter($this->datasourceService, $this->dataProcessorService);

        ImportExporter::resetData();
    }


    public function testCanGenerateResourcesForExport() {

        $datasources = [
            new DatasourceInstanceSearchResult("test1", "Test DS 1", "custom", "Test Datasource 1"),
            new DatasourceInstanceSearchResult("test2", "Test DS 2", "custom", "Test Datasource 2"),
            new DatasourceInstanceSearchResult("test3", "Test DS 3", "custom", "Test Datasource 3"),
        ];

        $this->datasourceService->returnValue("filterDatasourceInstances", $datasources, [
            "", PHP_INT_MAX, 0, ["custom", "document", "sqldatabase"], "testProject", 5
        ]);


        $exportables = $this->importExporter->getExportableProjectResources(5, "testProject");

        $this->assertEquals([
            new ProjectExportResource("test1", "Test DS 1", new DatasourceExportConfig(true, false)),
            new ProjectExportResource("test2", "Test DS 2", new DatasourceExportConfig(true, false)),
            new ProjectExportResource("test3", "Test DS 3", new DatasourceExportConfig(true, false)),
        ], $exportables);

    }


    public function testCanExportIncludedDatasourcesWithoutIncludedData() {

        $datasource1 = new DatasourceInstance("test1", "Test DS 1", "custom", ["tableName" => "hellotest1"], "dap_data", "usernamepassword");
        $datasource1->setImportKey("test1-import");
        $datasource2 = new DatasourceInstance("test2", "Test DS 2", "custom", ["tableName" => "hellotest2"], "dap_data", "usernamepassword");
        $datasource2->setImportKey("test2-import");

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource1, ["test1"]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource2, ["test2"]);

        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            "test1" => new DatasourceExportConfig(true, false),
            "test2" => new DatasourceExportConfig(true, false),
            "test3" => new DatasourceExportConfig(false, false),
        ], []);

        $this->assertEquals([
            new ExportedDatasource(-1, "Test DS 1", "custom", '', "test1-import", ["tableName" => null]),
            new ExportedDatasource(-2, "Test DS 2", "custom", '', "test2-import", ["tableName" => null]),
        ], $exportObjects);


    }

    public function testCanExportIncludedDatasourcesWithIncludedData() {

        $datasource1 = new DatasourceInstance("test3", "Test DS 3", "custom", ["tableName" => "hellotest3"], "dap_data", "usernamepassword");
        $datasource2 = new DatasourceInstance("test4", "Test DS 4", "custom", ["tableName" => "hellotest4"], "dap_data", "usernamepassword");

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource1, ["test3"]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource2, ["test4"]);

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", new ArrayTabularDataset([new Field("column1")], [
            ["column1" => "Value 1"],
            ["column1" => "value 2"]
        ]), ["test3"]);

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", new ArrayTabularDataset([new Field("column1"), new Field("column2")], [
            ["column1" => "Value 1", "column2" => "Update 1"],
            ["column1" => "value 2", "column2" => "Update 2"]
        ]), ["test4"]);


        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            "test3" => new DatasourceExportConfig(true, true),
            "test4" => new DatasourceExportConfig(true, true),
            "test5" => new DatasourceExportConfig(false, false),
        ], []);

        $this->assertEquals([
            new ExportedDatasource(-1, "Test DS 3", "custom", '', null, ["tableName" => null], [
                ["column1" => "Value 1"],
                ["column1" => "value 2"]
            ]),
            new ExportedDatasource(-2, "Test DS 4", "custom", '', null, ["tableName" => null], [
                ["column1" => "Value 1", "column2" => "Update 1"],
                ["column1" => "value 2", "column2" => "Update 2"]
            ]),
        ], $exportObjects);


    }


    public function testDataSourcesAreExportedForDataProcessorsWhichAreIncluded() {

        $fullExport = [
            "dataProcessors" => [
                "tabularsnapshot_5_12345" => new ObjectInclusionExportConfig(true),
                "querycaching_5_12345" => new ObjectInclusionExportConfig(true)
            ]
        ];

        $this->datasourceService->returnValue("filterDatasourceInstances", [
            new DatasourceInstanceSearchResult("tabularsnapshot_5_12345", "Historical Snapshot", "snapshot", "Historical Snap"),
            new DatasourceInstanceSearchResult("tabularsnapshot_5_12345_latest", "Latest Snapshot", "snapshot", "Latest Snap"),
            new DatasourceInstanceSearchResult("querycaching_5_12345_cache", "Query Cache", "querycache", "Query Cache"),
            new DatasourceInstanceSearchResult("querycaching_5_12345_caching", "Query Caching", "caching", "Caching Data Source")
        ], ["", PHP_INT_MAX, 0, ["snapshot", "querycache", "caching"], "testProject", 5]);

        $this->dataProcessorService->returnValue("getDataProcessorInstance", new DataProcessorInstance("tabularsnapshot_5_12345", "Snapshot", "snapshot"), [
            "tabularsnapshot_5_12345"
        ]);
        $this->dataProcessorService->returnValue("getDataProcessorInstance", new DataProcessorInstance("querycaching_5_12345", "Query Cache", "querycaching"), [
            "querycaching_5_12345"
        ]);

        $datasource1 = new DatasourceInstance("tabularsnapshot_5_12345", "Historical Snapshot", "snapshot", ["tableName" => "tabularsnapshot_5_12345"], "dap_data", "usernamepassword");
        $datasource2 = new DatasourceInstance("tabularsnapshot_5_12345_latest", "Latest Snapshot", "snapshot", ["tableName" => "tabularsnapshot_5_12345_latest"], "dap_data", "usernamepassword");
        $datasource3 = new DatasourceInstance("querycaching_5_12345_cache", "Query Cache", "querycache", ["tableName" => "querycaching_5_12345_cache"], "dap_data", "usernamepassword");
        $datasource4 = new DatasourceInstance("querycaching_5_12345_caching", "Query Caching", "caching", ["cacheDatasourceKey" => "querycaching_5_12345_cache", "sourceDatasetId" => 4], "dap_data", "usernamepassword");

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource1, ["tabularsnapshot_5_12345"]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource2, ["tabularsnapshot_5_12345_latest"]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource3, ["querycaching_5_12345_cache"]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource4, ["querycaching_5_12345_caching"]);


        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
        ], $fullExport);


        $this->assertEquals([
            new ExportedDatasource(-1, "Historical Snapshot", "snapshot", '', null, ["tableName" => null], [], -1, "Snapshot", "tabularsnapshot", ""),
            new ExportedDatasource(-2, "Latest Snapshot", "snapshot", '', null, ["tableName" => null], [], -1, "Snapshot", "tabularsnapshot", "_latest"),
            new ExportedDatasource(-3, "Query Cache", "querycache", '', null, ["tableName" => null], [], -2, "Query Cache", "querycaching", "_cache"),
            new ExportedDatasource(-4, "Query Caching", "caching", '', null, ["cacheDatasourceKey" => -3, "sourceDatasetId" => 4], [], -2, "Query Cache", "querycaching", "_caching")
        ], $exportObjects);


    }


    public function testCanAnalyseImportForDatasourcesFromExport() {

        $datasource1 = new DatasourceInstance("test1", "Test DS 1", "custom", ["tableName" => "hellotest1"], "dap_data", "usernamepassword");

        $this->datasourceService->returnValue("getDatasourceInstanceByTitle", $datasource1, [
            "Test DS 1", "testProject", 5
        ]);

        $this->datasourceService->throwException("getDatasourceInstanceByTitle", new ObjectNotFoundException(DatasourceInstance::class, "Test DS 1"), [
            "Test DS 2", "testProject", 5
        ]);

        $analysis = $this->importExporter->analyseImportObjects(5, "testProject", [
            new ExportedDatasource(-1, "Test DS 1", "custom", null, null, ["tableName" => null]),
            new ExportedDatasource(-2, "Test DS 2", "custom", null, null, ["tableName" => null]),
        ], [
            -1 => new DatasourceExportConfig(true, false),
            -2 => new DatasourceExportConfig(true, false)
        ], null);

        $this->assertEquals([
            new ProjectImportResource(-1, "Test DS 1", ProjectImportResourceStatus::Update, "test1"),
            new ProjectImportResource(-2, "Test DS 2", ProjectImportResourceStatus::Create)
        ], $analysis);

    }


    public function testCustomDatasourcesCreatedCorrectlyFromExport() {

        $datasource1 = new DatasourceInstance("test1", "Test DS 1", "custom", ["tableName" => "custom.test1"], "dap_data", "usernamepassword");

        $this->datasourceService->returnValue("getDatasourceInstanceByTitle", $datasource1, [
            "Test DS 1", "testProject", 5
        ]);

        $this->datasourceService->throwException("getDatasourceInstanceByTitle", new ObjectNotFoundException(DatasourceInstance::class, "Test DS 1"), [
            "Test DS 2", "testProject", 5
        ]);


        $this->importExporter->importObjects(5, "testProject", [
            new ExportedDatasource(-1, "Test DS 1", "custom", null, "testds1", ["tableName" => null, "otherProp" => 1]),
            new ExportedDatasource(-2, "Test DS 2", "custom", null, "testds2", ["tableName" => null, "otherProp" => 2], [
                ["column1" => "Test", "column2" => "Live"],
                ["column1" => "Test 2", "column2" => "Live 2"]
            ]),
        ], [
            -1 => new DatasourceExportConfig(true, false),
            -2 => new DatasourceExportConfig(true, true)
        ]);

        $credentialsKey = Configuration::readParameter("custom.datasource.credentials.key");


        // Check our updated one was updated intact
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance("test1", "Test DS 1", "custom", ["tableName" => "custom.test1", "otherProp" => 1], $credentialsKey, projectKey: "testProject", accountId: 5, importKey: "testds1")
        ]));


        // Check new one was created
        $newDatasourceKey = "custom_data_set_5_" . (intval(date("U")) + 1);
        $tableName = Configuration::readParameter("custom.datasource.table.prefix") . $newDatasourceKey;


        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance($newDatasourceKey, "Test DS 2", "custom", ["tableName" => $tableName, "otherProp" => 2], $credentialsKey, projectKey: "testProject", accountId: 5, importKey: "testds2")
        ]));


        // Check data was added to new one
        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", [
            $newDatasourceKey, new DatasourceUpdate([
                ["column1" => "Test", "column2" => "Live"],
                ["column1" => "Test 2", "column2" => "Live 2"]
            ])
        ]));


    }


    public function testDocumentDatasourcesCreatedCorrectlyFromExport() {

        $datasource1 = new DatasourceInstance("test1", "Test DS 1", "document", ["tableName" => "custom.test1"], "dap_data", "usernamepassword");

        $this->datasourceService->returnValue("getDatasourceInstanceByTitle", $datasource1, [
            "Test DS 1", "testProject", 5
        ]);

        $this->datasourceService->throwException("getDatasourceInstanceByTitle", new ObjectNotFoundException(DatasourceInstance::class, "Test DS 1"), [
            "Test DS 2", "testProject", 5
        ]);


        $this->importExporter->importObjects(5, "testProject", [
            new ExportedDatasource(-1, "Test DS 1", "document", null, null, ["tableName" => null, "otherProp" => 1]),
            new ExportedDatasource(-2, "Test DS 2", "document", null, null, ["tableName" => null, "otherProp" => 2], [
                ["column1" => "Test", "column2" => "Live"],
                ["column1" => "Test 2", "column2" => "Live 2"]
            ]),
        ], [
            -1 => new DatasourceExportConfig(true, false),
            -2 => new DatasourceExportConfig(true, true)
        ]);

        $credentialsKey = Configuration::readParameter("custom.datasource.credentials.key");


        // Check our updated one was updated intact
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance("test1", "Test DS 1", "document", ["tableName" => "custom.test1", "otherProp" => 1], $credentialsKey, projectKey: "testProject", accountId: 5)
        ]));


        // Check new one was created
        $newDatasourceKey = "document_data_set_5_" . (intval(date("U")) + 1);
        $tableName = Configuration::readParameter("custom.datasource.table.prefix") . $newDatasourceKey;


        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance($newDatasourceKey, "Test DS 2", "document", ["tableName" => $tableName, "otherProp" => 2], $credentialsKey, projectKey: "testProject", accountId: 5)
        ]));


        // Check data was added to new one
        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", [
            $newDatasourceKey, new DatasourceUpdate([
                ["column1" => "Test", "column2" => "Live"],
                ["column1" => "Test 2", "column2" => "Live 2"]
            ])
        ]));


    }

    public function testProcessorBasedDatasourcesImportedCorrectlyFromExport() {

        $this->dataProcessorService->returnValue("filterDataProcessorInstances", [
            new DataProcessorInstance("snapshot_5_12345", "Snapshot", "snapshot")
        ],
            [[], "testProject", 0, PHP_INT_MAX, 5]);


        $exportedObjects = [
            new ExportedDatasource(-1, "Historical Snapshot", "snapshot", '', null, ["tableName" => null], [], -1, "Snapshot", "tabularsnapshot", ""),
            new ExportedDatasource(-2, "Latest Snapshot", "snapshot", '', null, ["tableName" => null], [], -1, "Snapshot", "tabularsnapshot", "_latest"),
            new ExportedDatasource(-3, "Query Cache", "querycache", '', null, ["tableName" => "query_cache_3_cache"], [], -2, "Query Cache", "querycaching", "_cache"),
            new ExportedDatasource(-4, "Query Caching", "caching", '', null, ["cacheDatasourceKey" => -3, "sourceDatasetId" => -5], [], -2, "Query Cache", "querycaching", "_caching")
        ];


        // Import data processor related items
        $this->importExporter->importObjects(5, "testProject", $exportedObjects, []);


        // Check our updated data source one was left intact.
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance("snapshot_5_12345", "Historical Snapshot", "snapshot", ["tableName" => "snapshot.snapshot_5_12345"], "test", projectKey: "testProject", accountId: 5)
        ]));

        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance("snapshot_5_12345_latest", "Latest Snapshot", "snapshot", ["tableName" => "snapshot.snapshot_5_12345_latest"], "test", projectKey: "testProject", accountId: 5)
        ]));

        $queryCacheKey = "querycaching_5_" . date("U") . "_cache";
        $queryCachingKey = "querycaching_5_" . date("U") . "_caching";


        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance($queryCacheKey, "Query Cache", "querycache", ["tableName" => "query_cache." . $queryCacheKey], "test", projectKey: "testProject", accountId: 5)
        ]));

        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance($queryCachingKey, "Query Caching", "caching", ["cacheDatasourceKey" => $queryCacheKey, "sourceDatasetId" => -5], null, projectKey: "testProject", accountId: 5)
        ]));

    }


}