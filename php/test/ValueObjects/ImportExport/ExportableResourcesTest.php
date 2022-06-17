<?php


namespace Kinintel\Test\ValueObjects\ImportExport;

use Kinintel\Objects\Dashboard\DashboardSearchResult;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\ValueObjects\ImportExport\ExportableResources;

include_once "autoloader.php";

class ExportableResourcesTest extends \PHPUnit\Framework\TestCase {

    public function testDatasourceDatasetAndDashboardTitlesExtractedCorrectlyOnConstructionOfSearchResultObjects() {

        $datasourceInstanceSearchResults = [
            new DatasourceInstanceSearchResult("test-ds-1", "Test DS 1", "json"),
            new DatasourceInstanceSearchResult("test-ds-2", "Test DS 2", "json")
        ];

        $datasetInstanceSearchResults = [
            new DatasetInstanceSearchResult(25, "Test DI 1", "Hello world", "", []),
            new DatasetInstanceSearchResult(76, "Test DI 2", "Hello world", "", []),
            new DatasetInstanceSearchResult(99, "Test DI 3", "Hello world", "", [])
        ];

        $dashboardSummaries = [
            new DashboardSummary("Test DB 1", [], [], [], true, "", "", [], 11),
            new DashboardSummary("Test DB 2", [], [], [], true, "", "", [], 54)
        ];

        $exportableResources = new ExportableResources($datasourceInstanceSearchResults, $datasetInstanceSearchResults, $dashboardSummaries);

        $this->assertEquals([
            "test-ds-1" => "Test DS 1",
            "test-ds-2" => "Test DS 2"
        ], $exportableResources->getDatasourceInstances());

        $this->assertEquals([
            25 => "Test DI 1",
            76 => "Test DI 2",
            99 => "Test DI 3"
        ], $exportableResources->getDatasetInstances());

        $this->assertEquals([
            11 => "Test DB 1",
            54 => "Test DB 2"
        ], $exportableResources->getDashboards());


    }

    public function testInternalDependenciesEncodedCorrectlyWhereTheyExistForDashboardAndDatasets() {

        $datasourceInstanceSearchResults = [
            new DatasourceInstanceSearchResult("test-ds-1", "Test DS 1", "json"),
            new DatasourceInstanceSearchResult("test-ds-2", "Test DS 2", "json")
        ];

        $datasetInstanceSearchResults = [
            new DatasetInstanceSearchResult(25, "Test DI 1", "Hello world", "", [], 76),
            new DatasetInstanceSearchResult(76, "Test DI 2", "Hello world", "", [], null, "test-ds-2"),
            new DatasetInstanceSearchResult(99, "Test DI 3", "Hello world", "", [], 11)
        ];

        $dashboardSummaries = [
            new DashboardSummary("Test DB 1", [], [], [], true, "", "", [], 11, false, 54),
            new DashboardSummary("Test DB 2", [], [], [], true, "", "", [], 54, false, 9)
        ];

        $exportableResources = new ExportableResources($datasourceInstanceSearchResults, $datasetInstanceSearchResults, $dashboardSummaries);

        $this->assertEquals([
            "test-ds-2" => 76
        ], $exportableResources->getDatasourceDatasetDependencies());

        $this->assertEquals([
            76 => 25
        ], $exportableResources->getDatasetDependencies());


        $this->assertEquals([
            54 => 11
        ], $exportableResources->getDashboardDependencies());


    }

}