<?php

namespace Kinintel\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
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


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::mock(DatasourceService::class);
        $this->importExporter = new DatasourceImportExporter($this->datasourceService);
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
        $datasource2 = new DatasourceInstance("test2", "Test DS 2", "custom", ["tableName" => "hellotest2"], "dap_data", "usernamepassword");

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource1, ["test1"]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource2, ["test2"]);

        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            "test1" => new DatasourceExportConfig(true, false),
            "test2" => new DatasourceExportConfig(true, false),
            "test3" => new DatasourceExportConfig(false, false),
        ]);

        $this->assertEquals([
            new ExportedDatasource(-1, "Test DS 1", "custom", '', ["tableName" => null]),
            new ExportedDatasource(-2, "Test DS 2", "custom", '', ["tableName" => null]),
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
        ]);

        $this->assertEquals([
            new ExportedDatasource(-1, "Test DS 3", "custom", '', ["tableName" => null], [
                ["column1" => "Value 1"],
                ["column1" => "value 2"]
            ]),
            new ExportedDatasource(-2, "Test DS 4", "custom", '', ["tableName" => null], [
                ["column1" => "Value 1", "column2" => "Update 1"],
                ["column1" => "value 2", "column2" => "Update 2"]
            ]),
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
            new ExportedDatasource(-1, "Test DS 1", "custom", null, ["tableName" => null]),
            new ExportedDatasource(-2, "Test DS 2", "custom", null, ["tableName" => null]),
        ], [
            -1 => new DatasourceExportConfig(true, false),
            -2 => new DatasourceExportConfig(true, false)
        ]);

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
            new ExportedDatasource(-1, "Test DS 1", "custom", null, ["tableName" => null, "otherProp" => 1]),
            new ExportedDatasource(-2, "Test DS 2", "custom", null, ["tableName" => null, "otherProp" => 2], [
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
            new DatasourceInstance("test1", "Test DS 1", "custom", ["tableName" => "custom.test1", "otherProp" => 1], $credentialsKey, projectKey: "testProject", accountId: 5)
        ]));


        // Check new one was created
        $newDatasourceKey = "custom_data_set_5_" . date("U");
        $tableName = Configuration::readParameter("custom.datasource.table.prefix") . $newDatasourceKey;


        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            new DatasourceInstance($newDatasourceKey, "Test DS 2", "custom", ["tableName" => $tableName, "otherProp" => 2], $credentialsKey, projectKey: "testProject", accountId: 5)
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
            new ExportedDatasource(-1, "Test DS 1", "document", null, ["tableName" => null, "otherProp" => 1]),
            new ExportedDatasource(-2, "Test DS 2", "document", null, ["tableName" => null, "otherProp" => 2], [
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
        $newDatasourceKey = "document_data_set_5_" . date("U");
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



}