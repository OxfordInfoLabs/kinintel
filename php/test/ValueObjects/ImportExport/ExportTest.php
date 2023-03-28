<?php


namespace Kinintel\Test\ValueObjects\ImportExport;

use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\ValueObjects\ImportExport\Export;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class ExportTest extends \PHPUnit\Framework\TestCase {

    public function testCustomDatasourcesAddedToExportDirectlyWithUpdatedKeysAndTableName() {

        $sourceDatasource = new DatasourceInstance("myexampledatasource", "My Example Datasource", "custom", [
            "tableName" => "myexampledatasource",
            "otherProp" => "my value"
        ], "maindb");

        $sourceDatasource->setProjectKey("testproject");
        $sourceDatasource->setAccountId(1);


        $export = new Export(Export::SCOPE_PROJECT, "My Export", [$sourceDatasource], [], [], []);

        // Get datasources
        $datasources = $export->getDatasourceInstances();

        $this->assertEquals(1, sizeof($datasources));
        $expectedKeyHash = md5("DS::myexampledatasource");
        $this->assertEquals(new DatasourceInstance($expectedKeyHash, "My Example Datasource", "custom", [
            "tableName" => $expectedKeyHash,
            "otherProp" => "my value"
        ], "maindb"), $datasources[0]);

    }


    public function testSimpleDatasetsAreExportedAndOrderedByDataSetParentageWhereApplicableWithHashedIds() {

        $sourceDatasource = new DatasourceInstance("myexampledatasource", "My Example Datasource", "custom", [
            "tableName" => "myexampledatasource",
            "otherProp" => "my value"
        ], "maindb");

        // Has parent datasource included in export
        $dataset1 = new DatasetInstance(new DatasetInstanceSummary("Example 1", "myexampledatasource", null, [], [], [], null, null, [], null, 1),1, "myprojectkey");

        // Has external parent datasource
        $dataset2 = new DatasetInstance(new DatasetInstanceSummary("Example 2", "coredatasource", null, [], [], [], null, null, [], null, 2),1, "myprojectkey");

        // Has parent dataset included in export
        $dataset3 = new DatasetInstance(new DatasetInstanceSummary("Example 3", null, 4, [], [], [], null, null, [], null, 3),1, "myprojectkey");

        // Has parent dataset included in export and is parent of #3
        $dataset4 = new DatasetInstance(new DatasetInstanceSummary("Example 4", null, null, [], [], [], null, null, [], null, 4),1, "myprojectkey");

        // Is a parent of #4
        $dataset5 = new DatasetInstance(new DatasetInstanceSummary("Example 5", null, 3, [], [], [], null, null, [], null, 5),1, "myprojectkey");

        // Has external parent dataset
        $dataset6 = new DatasetInstance(new DatasetInstanceSummary("Example 6", null, 33, [], [], [], null, null, [], null, 6),1, "myprojectkey");


        $export = new Export(Export::SCOPE_PROJECT, "Dataset Export", [$sourceDatasource], [$dataset1, $dataset2, $dataset3, $dataset4, $dataset5, $dataset6], [], []);

        $datasets = $export->getDatasetInstances();


        // Check now ordered by internal parents where applicable and with hashed ids.
        $this->assertEquals([
            new DatasetInstance(new DatasetInstanceSummary("Example 6", null, 33, [], [], [], null, null, [], null, md5("DS::6"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 4", null, null, [], [], [], null, null, [], null, md5("DS::4"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 3", null, md5("DS::4"), [], [], [], null, null, [], null, md5("DS::3"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 5", null, md5("DS::3"), [], [], [], null, null, [], null, md5("DS::5"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 2", "coredatasource", null, [], [], [], null, null, [], null, md5("DS::2"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 1", md5("DS::myexampledatasource"), null, [], [], [], null, null, [], null, md5("DS::1"))),
        ], $datasets);

    }


    public function testDatasetsWithJoinTransformationsReferencingInternalDatasetsAreOrderedBySuchThatInternalTransformationDatasetsComeFirst() {

        $sourceDatasource = new DatasourceInstance("myexampledatasource", "My Example Datasource", "custom", [
            "tableName" => "myexampledatasource",
            "otherProp" => "my value"
        ], "maindb");

        // Has parent datasource included in export
        $dataset1 = new DatasetInstance(new DatasetInstanceSummary("Example 1", null, null, [
            new TransformationInstance("filter", new FilterTransformation([new Filter("test", "bingo")])),
            new TransformationInstance("join", new JoinTransformation(null, 2))
        ], [], [], null, null, [], null, 1));

        // Has external parent datasource
        $dataset2 = new DatasetInstance(new DatasetInstanceSummary("Example 2", null, null, [
            new TransformationInstance("join", new JoinTransformation("myexampledatasource"))
        ], [], [], null, null, [], null, 2));

        // Has parent dataset included in export
        $dataset3 = new DatasetInstance(new DatasetInstanceSummary("Example 3", null, null, [
            new TransformationInstance("join", new JoinTransformation(null, 1))
        ], [], [], null, null, [], null, 3));

        // Has external parent datasource
        $dataset4 = new DatasetInstance(new DatasetInstanceSummary("Example 4", null, null, [
            new TransformationInstance("join", new JoinTransformation("externalsource"))
        ], [], [], null, null, [], null, 4));

        // Has parent dataset included in export
        $dataset5 = new DatasetInstance(new DatasetInstanceSummary("Example 5", null, null, [
            new TransformationInstance("join", new JoinTransformation(null, 33))
        ], [], [], null, null, [], null, 5));

        $export = new Export(Export::SCOPE_PROJECT, "Dataset Export", [$sourceDatasource], [$dataset1, $dataset2, $dataset3, $dataset4, $dataset5], [], []);


        $this->assertEquals([
            new DatasetInstance(new DatasetInstanceSummary("Example 5", null, null, [
                new TransformationInstance("join", new JoinTransformation(null, 33))
            ], [], [], null, null, [], null, md5("DS::5"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 4", null, null, [
                new TransformationInstance("join", new JoinTransformation("externalsource"))
            ], [], [], null, null, [], null, md5("DS::4"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 2", null, null, [
                new TransformationInstance("join", new JoinTransformation(md5("DS::myexampledatasource")))
            ], [], [], null, null, [], null, md5("DS::2"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 1", null, null, [
                new TransformationInstance("filter", new FilterTransformation([new Filter("test", "bingo")])),
                new TransformationInstance("join", new JoinTransformation(null, md5("DS::2")))
            ], [], [], null, null, [], null, md5("DS::1"))),
            new DatasetInstance(new DatasetInstanceSummary("Example 3", null, null, [
                new TransformationInstance("join", new JoinTransformation(null, md5("DS::1")))
            ], [], [], null, null, [], null, md5("DS::3")))


        ], $export->getDatasetInstances());


    }


    public function testSimpleDashboardsAreExportedInParentageOrderAndContainedDatasetInstancesAndJoinTransformationsAreMappedToHashedValuesWhereApplicable() {

        $datasource = new DatasourceInstance("myexampledatasource", "My Example Datasource", "custom", [
            "tableName" => "myexampledatasource",
            "otherProp" => "my value"
        ], "maindb");

        // Dataset for attachment
        $dataset = new DatasetInstance(new DatasetInstanceSummary("Example 1", null, null, [
            new TransformationInstance("filter", new FilterTransformation([new Filter("test", "bingo")])),
            new TransformationInstance("join", new JoinTransformation(null, 2))
        ], [], [], null, null, [], null, 1));


        $dashboard1 = new Dashboard(new DashboardSummary("Dashboard 1", [
            new DashboardDatasetInstance("instance1", 1),
            new DashboardDatasetInstance("instance2", null, "myexampledatasource"),
            new DashboardDatasetInstance("instance3", 12, null, [new TransformationInstance("filter", new FilterTransformation([new Filter("test", "bingo")])),
                new TransformationInstance("join", new JoinTransformation(null, 1))]),
            new DashboardDatasetInstance("instance4", null, "externalds", [
                new TransformationInstance("join", new JoinTransformation("myexampledatasource", null))
            ])
        ], null, null, null, false, [], null, null, [], 1, false, 2),1, "myprojectkey");
        $dashboard2 = new Dashboard(new DashboardSummary("Dashboard 2", [], null, null, null, false, [], null, null, [], 2, false, 23),1, "myprojectkey");
        $dashboard3 = new Dashboard(new DashboardSummary("Dashboard 3", [], null, null, null, false, [], null, null, [], 3, false, 1),1, "myprojectkey");


        $export = new Export(Export::SCOPE_PROJECT, "Dashboard Export", [$datasource], [$dataset], [$dashboard1, $dashboard2, $dashboard3], []);

        $this->assertEquals([
            new Dashboard(new DashboardSummary("Dashboard 2", [], null, null, null, false, [], null, null, [],
                md5("DB::2"), false, 23)),
            new Dashboard(new DashboardSummary("Dashboard 1", [
                new DashboardDatasetInstance("instance1", md5("DS::1")),
                new DashboardDatasetInstance("instance2", null, md5("DS::myexampledatasource")),
                new DashboardDatasetInstance("instance3", 12, null, [new TransformationInstance("filter", new FilterTransformation([new Filter("test", "bingo")])),
                    new TransformationInstance("join", new JoinTransformation(null, md5("DS::1")))]),
                new DashboardDatasetInstance("instance4", null, "externalds", [
                    new TransformationInstance("join", new JoinTransformation(md5("DS::myexampledatasource"), null))
                ])
            ], null, null, null, false, [], null, null, [], md5("DB::1"), false, md5("DB::2"))),
            new Dashboard(new DashboardSummary("Dashboard 3", [], null, null, null, false, [], null, null, [], md5("DB::3"), false, md5("DB::1")))
        ], $export->getDashboards());

    }


}