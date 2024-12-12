<?php

namespace Kinintel\Test\Services\ImportExport\ImportExporters;

use Aws\Scheduler\Exception\SchedulerException;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\ImportExport\ImportExporters\DataProcessorImportExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\DataProcessor\DataProcessorItem;

include_once "autoloader.php";

class DataProcessorImportExporterTest extends TestBase {

    /**
     * @var DataProcessorImportExporter
     */
    private $importExporter;

    /**
     * @var DataProcessorService|MockObject
     */
    private $dataProcessorService;

    public function setUp(): void {
        $this->dataProcessorService = MockObjectProvider::mock(DataProcessorService::class);
        $this->importExporter = new DataProcessorImportExporter($this->dataProcessorService);
        ImportExporter::resetData();
    }


    public function testCanGetExportableProjectResources() {

        $this->dataProcessorService->returnValue("filterDataProcessorInstances", [
            new DataProcessorInstance("test-processor-1", "Test Processor 1", "tabulardatasetsnapshot"),
            new DataProcessorInstance("test-processor-2", "Test Processor 2", "querycaching")
        ],
            [[], "testProject", 0, PHP_INT_MAX, 5]);


        $resources = $this->importExporter->getExportableProjectResources(5, "testProject");

        $this->assertEquals([
            new ProjectExportResource("test-processor-1", "Test Processor 1", new ObjectInclusionExportConfig(true), "snapshot"),
            new ProjectExportResource("test-processor-2", "Test Processor 2", new ObjectInclusionExportConfig(true), "querycaching")
        ], $resources);

    }


    public function testCanCreateExportObjectsFromExportConfig() {

        $firstInstance = new DataProcessorInstance("testkey1", "Test Snapshot", "tabulardatasetsnapshot", ["createHistory" => true, "keyFieldNames" => ["title", "type"]],
            DataProcessorInstance::TRIGGER_SCHEDULED, new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "Daily Run", [], [new ScheduledTaskTimePeriod(null, null, 11, 25)])), "DatasetInstance", 55, "testProject", 5);
        $secondInstance = new DataProcessorInstance("testkey2", "Test Query Cache", "querycaching", ["sourceQueryId" => 66, "primaryKeyColumnNames" => ["title", "type"]],
            DataProcessorInstance::TRIGGER_ADHOC, new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "Daily Run", [], [])), "DatasetInstance", 66, "testProject", 5);

        $this->dataProcessorService->returnValue("getDataProcessorInstance", $firstInstance, ["testkey1"]);
        $this->dataProcessorService->returnValue("getDataProcessorInstance", $secondInstance, ["testkey2"]);

        // Programme a couple of dependency mappings
        ImportExporter::getNewExportPK("datasets", 55);
        ImportExporter::getNewExportPK("datasets", 66);


        $exportObjects = $this->importExporter->createExportObjects(5, "testProject", [
            "testkey1" => new ObjectInclusionExportConfig(true),
            "testkey2" => new ObjectInclusionExportConfig(true)
        ], []);

        $this->assertEquals([
            new DataProcessorItem("Test Snapshot", "tabulardatasetsnapshot", ["createHistory" => true, "keyFieldNames" => ["title", "type"]],
                DataProcessorInstance::TRIGGER_SCHEDULED, "DatasetInstance", -1, null,
                [new ScheduledTaskTimePeriod(null, null, 11, 25)], ScheduledTask::STATUS_PENDING, null, null, null,
                -1),
            new DataProcessorItem("Test Query Cache", "querycaching", ["sourceQueryId" => -2, "primaryKeyColumnNames" => ["title", "type"]], DataProcessorInstance::TRIGGER_ADHOC, "DatasetInstance", -2, null, [], ScheduledTask::STATUS_PENDING, null, null, null, -2)
        ], $exportObjects);


    }


    public function testCanAnalyseImportUsingExportObjectsAndConfig() {

        $this->dataProcessorService->returnValue("filterDataProcessorInstances", [
            new DataProcessorInstance("test-processor-1", "Test Snapshot", "tabulardatasetsnapshot")
        ],
            [[], "testProject", 0, PHP_INT_MAX, 5]);

        $exportObjects = [
            new DataProcessorItem("Test Snapshot", "tabulardatasetsnapshot", ["createHistory" => true, "keyFieldNames" => ["title", "type"]],
                DataProcessorInstance::TRIGGER_SCHEDULED, "DatasetInstance", -1, null,
                [new ScheduledTaskTimePeriod(null, null, 11, 25)], ScheduledTask::STATUS_PENDING, null, null, null,
                -1),
            new DataProcessorItem("Test Query Cache", "querycaching", ["sourceQueryId" => -2, "primaryKeyColumnNames" => ["title", "type"]], DataProcessorInstance::TRIGGER_ADHOC, "DatasetInstance", -2, null, [], ScheduledTask::STATUS_PENDING, null, null, null, -2)
        ];

        $exportConfig = [
            -1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true)
        ];


        $importAnalysis = $this->importExporter->analyseImportObjects(5, "testProject", $exportObjects, $exportConfig, null);

        $this->assertEquals([
            new ProjectImportResource(-1, "Test Snapshot", ProjectImportResourceStatus::Update, "test-processor-1", "Snapshots"),
            new ProjectImportResource(-2, "Test Query Cache", ProjectImportResourceStatus::Create, null, "Query Caches")
        ], $importAnalysis);

    }


    public function testCanImportDataProcessorsFromObjectsAndConfig() {

        $this->dataProcessorService->returnValue("filterDataProcessorInstances", [
            new DataProcessorInstance("test-processor-1", "Test Snapshot", "tabulardatasetsnapshot")
        ],
            [[], "testProject", 0, PHP_INT_MAX, 5]);

        $exportObjects = [
            new DataProcessorItem("Test Snapshot", "tabulardatasetsnapshot", ["createHistory" => true, "keyFieldNames" => ["title", "type"]],
                DataProcessorInstance::TRIGGER_SCHEDULED, "DatasetInstance", -1, null,
                [new ScheduledTaskTimePeriod(null, null, 11, 25)], ScheduledTask::STATUS_PENDING, null, null, null,
                -1),
            new DataProcessorItem("Test Query Cache", "querycaching", ["sourceQueryId" => -2, "primaryKeyColumnNames" => ["title", "type"]], DataProcessorInstance::TRIGGER_ADHOC, "DatasetInstance", -2, null, [], ScheduledTask::STATUS_PENDING, null, null, null, -2)
        ];

        $exportConfig = [
            -1 => new ObjectInclusionExportConfig(true),
            -2 => new ObjectInclusionExportConfig(true)
        ];

        // Configure new ids for datasets.
        ImportExporter::setImportItemIdMapping("datasets", -1, 100);
        ImportExporter::setImportItemIdMapping("datasets", -2, 101);


        // Programme return value for processor save
        $newProcessorKey = "querycaching_5_" . date("U");
        $this->dataProcessorService->returnValue("saveDataProcessorInstance", $newProcessorKey, [
            (new DataProcessorItem("Test Query Cache", "querycaching", ["sourceQueryId" => 101, "primaryKeyColumnNames" => ["title", "type"]],
                DataProcessorInstance::TRIGGER_ADHOC, "DatasetInstance", 101, null, [],
                ScheduledTask::STATUS_PENDING, null, null, null, $newProcessorKey))->toDataProcessorInstance("testProject", 5)
        ]);

        $this->importExporter->importObjects(5, "testProject", $exportObjects, $exportConfig);


        $this->assertTrue($this->dataProcessorService->methodWasCalled("saveDataProcessorInstance", [
            (new DataProcessorItem("Test Snapshot", "tabulardatasetsnapshot", ["createHistory" => true, "keyFieldNames" => ["title", "type"]],
                DataProcessorInstance::TRIGGER_SCHEDULED, "DatasetInstance", 100, null,
                [new ScheduledTaskTimePeriod(null, null, 11, 25)], ScheduledTask::STATUS_PENDING, null, null, null,
                "test-processor-1"))->toDataProcessorInstance("testProject", 5)
        ]));


        $this->assertTrue($this->dataProcessorService->methodWasCalled("saveDataProcessorInstance", [
            (new DataProcessorItem("Test Query Cache", "querycaching", ["sourceQueryId" => 101, "primaryKeyColumnNames" => ["title", "type"]],
                DataProcessorInstance::TRIGGER_ADHOC, "DatasetInstance", 101, null, [],
                ScheduledTask::STATUS_PENDING, null, null, null, $newProcessorKey))->toDataProcessorInstance("testProject", 5)
        ]));


        $this->assertTrue($this->dataProcessorService->methodWasCalled("triggerDataProcessorInstance", [$newProcessorKey]));
    }

}