<?php

namespace Kinintel\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\Hook\DatasourceHookService;
use Kinintel\Services\ImportExport\ImportExporters\DatasourceHookImportExporter;
use Kinintel\TestBase;

include_once "autoloader.php";

class DatasourceHookImportExporterTest extends TestBase {

    /**
     * @var DatasourceHookImportExporter
     */
    private $importExporter;

    /**
     * @var DatasourceHookService
     */
    private $datasourceHookService;

    public function setUp(): void {
        $this->datasourceHookService = MockObjectProvider::instance()->getMockInstance(DatasourceHookService::class);
        $this->importExporter = new DatasourceHookImportExporter($this->datasourceHookService);
        ImportExporter::resetData();
    }

    public function testCanGenerateResourcesForExport() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $hook1 = new DatasourceHookInstance("Hook 1", id: 205);
        $hook2 = new DatasourceHookInstance("Hook 2", id: 206);

        $hook1->save();
        $hook2->save();

        $this->datasourceHookService->returnValue("filterDatasourceHookInstances", [$hook1, $hook2], ["testProject", 0, PHP_INT_MAX, 5]);


        $resources = $this->importExporter->getExportableProjectResources(5, "testProject");

        $this->assertEquals([
            new ProjectExportResource(205, "Hook 1", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(206, "Hook 2", new ObjectInclusionExportConfig(true))
        ], $resources);
    }

    public function testCanCreateExportObjectsForHooks() {

        $this->datasourceHookService->returnValue("getDatasourceHookById", new DatasourceHookInstance("Hook 1", "datasourceKey1", scheduledTaskId: 35, id: 22), [22]);
        $this->datasourceHookService->returnValue("getDatasourceHookById", new DatasourceHookInstance("Hook 2", "datasourceKey2", dataProcessorInstanceKey: "dataProcessorKey", id: 33), [33]);

        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            22 => new ObjectInclusionExportConfig(true),
            33 => new ObjectInclusionExportConfig(true)
        ], []);

        $this->assertEquals([
            new DatasourceHookInstance("Hook 1", "datasourceKey1", scheduledTaskId: 35, id: -1),
            new DatasourceHookInstance("Hook 2", "datasourceKey2", dataProcessorInstanceKey: "dataProcessorKey", id: -2)
        ], $exportObjects);

    }

    public function testCanCreateExportObjectsForHooksWhereDependenciesHaveBeenMapped() {

        $this->datasourceHookService->returnValue("getDatasourceHookById", new DatasourceHookInstance("Hook 1", "localSource", scheduledTaskId: 35, id: 22), [22]);
        $this->datasourceHookService->returnValue("getDatasourceHookById", new DatasourceHookInstance("Hook 2", "localSource", dataProcessorInstanceKey: "globalProcessor", id: 33), [33]);
        $this->datasourceHookService->returnValue("getDatasourceHookById", new DatasourceHookInstance("Hook 3", "globalSource", dataProcessorInstanceKey: "localProcessor", id: 44), [44]);

        ImportExporter::getNewExportPK("datasources", "localSource");
        ImportExporter::getNewExportPK("dataProcessors", "localProcessor");

        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            22 => new ObjectInclusionExportConfig(true),
            33 => new ObjectInclusionExportConfig(true),
            44 => new ObjectInclusionExportConfig(true)
        ], []);

        $this->assertEquals([
            new DatasourceHookInstance("Hook 1", -1, scheduledTaskId: 35, id: -1),
            new DatasourceHookInstance("Hook 2", -1, dataProcessorInstanceKey: "globalProcessor", id: -2),
            new DatasourceHookInstance("Hook 3", "globalSource", dataProcessorInstanceKey: -1, id: -3)
        ], $exportObjects);

    }

    public function testCanAnalyseImportForHooks() {

        $exportObjects = [
            new DatasourceHookInstance("Hook 1", -1, scheduledTaskId: 35, id: -1),
            new DatasourceHookInstance("Hook 2", -1, dataProcessorInstanceKey: "globalProcessor", id: -2),
            new DatasourceHookInstance("Hook 3", "globalSource", dataProcessorInstanceKey: -1, id: -3)
        ];

        $exportConfig = [
            -1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true),
            -3 => new ObjectInclusionExportConfig(true)
        ];

        $this->datasourceHookService->returnValue("filterDatasourceHookInstances", [
            new DatasourceHookInstance("Hook 2", id: 22),
            new DatasourceHookInstance("Hook 3", id: 33)
        ], [
            "testProject", 0, PHP_INT_MAX, 5
        ]);


        $analysis = $this->importExporter->analyseImportObjects(5, "testProject", $exportObjects, $exportConfig);
        $this->assertEquals([
            new ProjectImportResource(-1, "Hook 1", ProjectImportResourceStatus::Create),
            new ProjectImportResource(-2, "Hook 2", ProjectImportResourceStatus::Update, 22),
            new ProjectImportResource(-3, "Hook 3", ProjectImportResourceStatus::Update, 33),
        ], $analysis);
    }

    public function testCanImportHooksAndMatchExistingIdsForDependencies() {

        $exportObjects = [
            new DatasourceHookInstance("Hook 1", -1, scheduledTaskId: 35, id: -1),
            new DatasourceHookInstance("Hook 2", -1, dataProcessorInstanceKey: "globalProcessor", id: -2),
            new DatasourceHookInstance("Hook 3", "globalSource", dataProcessorInstanceKey: -1, id: -3)
        ];

        $exportConfig = [
            -1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true),
            -3 => new ObjectInclusionExportConfig(true)
        ];

        $this->datasourceHookService->returnValue("filterDatasourceHookInstances", [
            new DatasourceHookInstance("Hook 2", id: 22),
            new DatasourceHookInstance("Hook 3", id: 33)
        ], [
            "testProject", 0, PHP_INT_MAX, 5
        ]);

        $this->datasourceHookService->returnValue("saveHookInstance", 99, [
            new DatasourceHookInstance("Hook 1", -1, scheduledTaskId: 35), "testProject", 5
        ]);

        // Programme out datasource
        ImportExporter::setImportItemIdMapping("datasources", -1, "localSource");
        ImportExporter::setImportItemIdMapping("dataProcessors", -1, "localProcessor");

        $this->importExporter->importObjects(5, "testProject", $exportObjects, $exportConfig);


        // Check our instances were saved
        $this->assertTrue($this->datasourceHookService->methodWasCalled("saveHookInstance", [new DatasourceHookInstance("Hook 1", "localSource", scheduledTaskId: 35, id: 99), "testProject", 5]));
        $this->assertTrue($this->datasourceHookService->methodWasCalled("saveHookInstance", [new DatasourceHookInstance("Hook 2", "localSource", dataProcessorInstanceKey: "globalProcessor", id: 22), "testProject", 5]));
        $this->assertTrue($this->datasourceHookService->methodWasCalled("saveHookInstance", [new DatasourceHookInstance("Hook 3", "globalSource", dataProcessorInstanceKey: "localProcessor", id: 33), "testProject", 5]));

    }

}