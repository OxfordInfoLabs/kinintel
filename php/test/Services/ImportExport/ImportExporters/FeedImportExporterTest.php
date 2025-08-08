<?php

namespace Kinintel\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Services\Feed\FeedService;
use Kinintel\Services\ImportExport\ImportExporters\FeedImportExporter;
use Kinintel\TestBase;

include_once "autoloader.php";

class FeedImportExporterTest extends TestBase {

    /**
     * @var FeedImportExporter
     */
    private $importExporter;

    /**
     * @var FeedService
     */
    private $feedService;


    public function setUp(): void {
        $this->feedService = MockObjectProvider::mock(FeedService::class);
        $this->importExporter = new FeedImportExporter($this->feedService);

        ImportExporter::resetData();
    }

    public function testCanGetAllExportableResources() {

        $this->feedService->returnValue("filterFeeds", [
            new FeedSummary("/test1", 1, ["input1"], "json", [], false, '', 0, null, 55),
            new FeedSummary("/test2", 2, ["input2"], "json", [], false, '', 0, null, 66),
            new FeedSummary("/test3", 3, ["input3"], "json", [], false, '', 0, null, 77),
        ], [
            "", "testProject", 0, PHP_INT_MAX, 5
        ]);


        $resources = $this->importExporter->getExportableProjectResources(5, "testProject");

        $this->assertEquals([
            new ProjectExportResource(55, "/test1", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(66, "/test2", new ObjectInclusionExportConfig(true)),
            new ProjectExportResource(77, "/test3", new ObjectInclusionExportConfig(true)),
        ], $resources);
    }


    public function testCanCreateExportResourcesWithSubstitutedValues() {

        $this->feedService->returnValue("filterFeeds", [
            new FeedSummary("/test1", 1, ["input1"], "json", [], false, '', 0, null, 55),
            new FeedSummary("/test2", 2, ["input2"], "json", [], false, '', 0, null, 66),
            new FeedSummary("/test3", 3, ["input3"], "json", [], false, '', 0, null, 77),
        ], [
            "", "testProject", 0, PHP_INT_MAX, 5
        ]);

        // Create mappings for the datasets
        ImportExporter::getNewExportPK("datasets", 1);
        ImportExporter::getNewExportPK("datasets", 2);
        ImportExporter::getNewExportPK("datasets", 3);


        $exported = $this->importExporter->createExportObjects(5, "testProject", [
            55 => new ObjectInclusionExportConfig(true),
            66 => new ObjectInclusionExportConfig(false),
            77 => new ObjectInclusionExportConfig(true)
        ], []);

        $this->assertEquals([
            new FeedSummary("/test1", -1, ["input1"], "json", [], false, '', 0, null, -1),
            new FeedSummary("/test3", -3, ["input3"], "json", [], false, '', 0, null, -2),
        ], $exported);

    }


    public function testCanAnalyseImportForFeedExport() {

        $this->feedService->returnValue("filterFeeds", [
            new FeedSummary("/test1", 1, ["input1"], "json", [], false, '', 0, null, 55),
            new FeedSummary("/test2", 2, ["input2"], "json", [], false, '', 0, null, 66),
        ], [
            "", "testProject", 0, PHP_INT_MAX, 5
        ]);


        $analysis = $this->importExporter->analyseImportObjects(5, "testProject", [
            new FeedSummary("/test1", -1, ["input1"], "json", [], false, '', 0, null, -1),
            new FeedSummary("/test3", -3, ["input3"], "json", [], false, '', 0, null, -2),
        ], []);


        $this->assertEquals([
            new ProjectImportResource(-1, "//test1", ProjectImportResourceStatus::Update, 55),
            new ProjectImportResource(-2, "//test3", ProjectImportResourceStatus::Create)
        ], $analysis);


    }

    public function testCanImportFeedsFromExport() {

        $this->feedService->returnValue("filterFeeds", [
            new FeedSummary("/test1", 1, ["input1"], "json", [], false, '', 0, null, 55),
            new FeedSummary("/test2", 2, ["input2"], "json", [], false, '', 0, null, 66),
        ], [
            "", "testProject", 0, PHP_INT_MAX, 5
        ]);

        ImportExporter::setImportItemIdMapping("datasets", -1, 33);
        ImportExporter::setImportItemIdMapping("datasets", -3, 44);


        $this->importExporter->importObjects(5, "testProject", [
            new FeedSummary("/test1", -1, ["input1"], "json", [], false, '', 0, null, -1),
            new FeedSummary("/test3", -3, ["input3"], "json", [], false, '', 0, null, -2),
        ], []);


        $this->assertTrue($this->feedService->methodWasCalled("saveFeed",[
            new FeedSummary("/test1", 33, ["input1"], "json", [], false, '', 0, null, 55),
            "testProject",
            5
        ]));

        $this->assertTrue($this->feedService->methodWasCalled("saveFeed",[
            new FeedSummary("/test3", 44, ["input3"], "json", [], false, '', 0, null),
            "testProject",
            5
        ]));

    }


}