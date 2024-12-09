<?php

namespace Kinintel\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\ImportExport\ImportExporters\DatasetImportExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\ImportExport\ExportConfig\DatasourceExportConfig;
use Kinintel\ValueObjects\Transformation\Combine\CombineTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class DatasetImportExporterTest extends TestBase {

    /**
     * @var DatasetImportExporter
     */
    private $importExporter;

    /**
     * @var DatasetService
     */
    private $datasetService;


    public function setUp(): void {
        $this->datasetService = MockObjectProvider::mock(DatasetService::class);
        $this->importExporter = new DatasetImportExporter($this->datasetService);
        ImportExporter::resetData();
    }

    public function testCanGenerateResourcesForExport() {

        $this->datasetService->returnValue("filterDataSetInstances", [
            new DatasetInstanceSearchResult(22, "Dataset 22"),
            new DatasetInstanceSearchResult(33, "Dataset 33")
        ], [
            "", [], [], "testProject", 0, PHP_INT_MAX, 5
        ]);

        $objects = $this->importExporter->getExportableProjectResources(5, "testProject");
        $this->assertEquals([
            new ProjectExportResource(22, "Dataset 22", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(33, "Dataset 33", new ObjectInclusionExportConfig(true))
        ], $objects);

    }

    public function testCanCreateExportObjectsForSimpleNonRecursiveQueries() {

        $this->datasetService->returnValue("getDataSetInstance", new DatasetInstanceSummary("DS 1", "globalSource", null, [], [], [], null, null, [], 33), [33]);
        $this->datasetService->returnValue("getDataSetInstance", new DatasetInstanceSummary("DS 2", null, 55, [], [], [], null, null, [], 44), [44]);

        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            33 => new ObjectInclusionExportConfig(true),
            44 => new ObjectInclusionExportConfig(true)
        ], []);


        $this->assertEquals([
            new DatasetInstanceSummary("DS 1", "globalSource", null, [], [], [], null, null, [], -1),
            new DatasetInstanceSummary("DS 2", null, 55, [], [], [], null, null, [], -2)
        ], $exportObjects);


    }


    public function testCanCreateExportObjectsForQueriesWhichDependOnOtherAccountQueriesAndTheyHaveRemappedIdsAsExpected() {

        $this->datasetService->returnValue("getDataSetInstance", new DatasetInstanceSummary("DS 1", "globalSource", null, [], [], [], null, null, [], 33), [33]);
        $this->datasetService->returnValue("getDataSetInstance", new DatasetInstanceSummary("DS 2", "localSource", null, [], [], [], null, null, [], 22), [22]);
        $this->datasetService->returnValue("getDataSetInstance", new DatasetInstanceSummary("DS 3", null, 33, [], [], [], null, null, [], 44), [44]);

        ImportExporter::getNewExportPK("datasources", "localSource");

        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            22 => new ObjectInclusionExportConfig(true),
            44 => new ObjectInclusionExportConfig(true),
            33 => new ObjectInclusionExportConfig(true)
        ], []);


        $this->assertEquals([
            new DatasetInstanceSummary("DS 2", -1, null, [], [], [], null, null, [], -1),
            new DatasetInstanceSummary("DS 3", null, -3, [], [], [], null, null, [], -2),
            new DatasetInstanceSummary("DS 1", "globalSource", null, [], [], [], null, null, [], -3)
        ], $exportObjects);

    }


    public function testCanCreateExportObjectsForQueriesWithRemappedJoinOrCombineTransformationIdentifiers() {

        $this->datasetService->returnValue("getDataSetInstance", new DatasetInstanceSummary("DS 1", "globalSource", null, [new TransformationInstance("join", new JoinTransformation("localSource")), new TransformationInstance("combine", new CombineTransformation(null, 44))], [], [], null, null, [], 33), [33]);
        $this->datasetService->returnValue("getDataSetInstance", new DatasetInstanceSummary("DS 2", "localSource", null, [new TransformationInstance("join", new JoinTransformation(null, 44)), new TransformationInstance("combine", new CombineTransformation("localSource"))], [], [], null, null, [], 22), [22]);
        $this->datasetService->returnValue("getDataSetInstance", new DatasetInstanceSummary("DS 3", null, 33, [], [], [], null, null, [], 44), [44]);

        ImportExporter::getNewExportPK("datasources", "localSource");

        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            22 => new ObjectInclusionExportConfig(true),
            44 => new ObjectInclusionExportConfig(true),
            33 => new ObjectInclusionExportConfig(true)
        ], []);


        $this->assertEquals([
            new DatasetInstanceSummary("DS 2", -1, null, [new TransformationInstance("join", new JoinTransformation(null, -2)), new TransformationInstance("combine", new CombineTransformation(-1))], [], [], null, null, [], -1),
            new DatasetInstanceSummary("DS 3", null, -3, [], [], [], null, null, [], -2),
            new DatasetInstanceSummary("DS 1", "globalSource", null, [new TransformationInstance("join", new JoinTransformation(-1)), new TransformationInstance("combine", new CombineTransformation(null, -2))], [], [], null, null, [], -3)
        ], $exportObjects);

    }


    public function testCanAnalyseImportForQueries() {

        $exportObjects = [
            new DatasetInstanceSummary("DS 2", -1, null, [new TransformationInstance("join", new JoinTransformation(null, -2)), new TransformationInstance("combine", new CombineTransformation(-1))], [], [], null, null, [], -1),
            new DatasetInstanceSummary("DS 3", null, -3, [], [], [], null, null, [], -2),
            new DatasetInstanceSummary("DS 1", "globalSource", null, [new TransformationInstance("join", new JoinTransformation(-1)), new TransformationInstance("combine", new CombineTransformation(null, -2))], [], [], null, null, [], -3)
        ];

        $exportConfig = [
            -1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true),
            -3 => new ObjectInclusionExportConfig(true)
        ];

        $this->datasetService->returnValue("filterDataSetInstances", [
            new DatasetInstanceSearchResult(22, "DS 2"),
            new DatasetInstanceSearchResult(33, "DS 3")
        ], [
            "", [], [], "testProject", 0, PHP_INT_MAX, 5
        ]);


        $analysis = $this->importExporter->analyseImportObjects(5, "testProject", $exportObjects, $exportConfig, null);
        $this->assertEquals([
            new ProjectImportResource(-1, "DS 2", ProjectImportResourceStatus::Update, 22),
            new ProjectImportResource(-2, "DS 3", ProjectImportResourceStatus::Update, 33),
            new ProjectImportResource(-3, "DS 1", ProjectImportResourceStatus::Create),
        ], $analysis);

    }


    public function testCanImportQueriesAndMatchExistingIdsForDependenciesAndTransformations() {

        $exportObjects = [
            new DatasetInstanceSummary("DS 2", -1, null, [new TransformationInstance("join", new JoinTransformation(null, -2)), new TransformationInstance("combine", new CombineTransformation(-1))], [], [], null, null, [], -1),
            new DatasetInstanceSummary("DS 3", null, -3, [], [], [], null, null, [], -2),
            new DatasetInstanceSummary("DS 1", "globalSource", null, [new TransformationInstance("join", new JoinTransformation(-1)), new TransformationInstance("combine", new CombineTransformation(null, -2))], [], [], null, null, [], -3)
        ];

        $exportConfig = [
            -1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true),
            -3 => new ObjectInclusionExportConfig(true)
        ];

        $this->datasetService->returnValue("filterDataSetInstances", [
            new DatasetInstanceSearchResult(22, "DS 2"),
            new DatasetInstanceSearchResult(33, "DS 3")
        ], [
            "", [], [], "testProject", 0, PHP_INT_MAX, 5
        ]);

        $this->datasetService->returnValue("saveDataSetInstance", 99, [
            new DatasetInstanceSummary("DS 1", "globalSource", null, [new TransformationInstance("join", new JoinTransformation(-1)), new TransformationInstance("combine", new CombineTransformation(null, -2))], [], [], null, null, [], null), "testProject", 5
        ]);

        // Programme out datasource
        ImportExporter::setImportItemIdMapping("datasources", -1, "localSource");

        $this->importExporter->importObjects(5, "testProject", $exportObjects, $exportConfig);


        // Check our instances were saved
        $this->assertTrue($this->datasetService->methodWasCalled("saveDataSetInstance", [new DatasetInstanceSummary("DS 2", "localSource", null, [new TransformationInstance("join", new JoinTransformation(null, 33)), new TransformationInstance("combine", new CombineTransformation("localSource"))], [], [], null, null, [], 22), "testProject", 5]));
        $this->assertTrue($this->datasetService->methodWasCalled("saveDataSetInstance", [new DatasetInstanceSummary("DS 3", null, 99, [], [], [], null, null, [], 33), "testProject", 5]));
        $this->assertTrue($this->datasetService->methodWasCalled("saveDataSetInstance", [new DatasetInstanceSummary("DS 1", "globalSource", null, [new TransformationInstance("join", new JoinTransformation("localSource")), new TransformationInstance("combine", new CombineTransformation(null, 33))], [], [], null, null, [], 99), "testProject", 5]));

    }


}