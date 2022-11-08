<?php


namespace Kinintel\Test\Services\DataProcessor\DatasetSnapshot;

use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\TableMapper\Mapper\TableMapper;
use Kinikit\Persistence\TableMapper\Mapper\TableMapping;
use Kinintel\Controllers\Account\Datasource;
use Kinintel\Controllers\API\Dataset;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\DataProcessor\DatasetSnapshot\TabularDatasetSnapshotProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetSnapshotProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TimeLapseFieldSet;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class TabularDatasetSnapshotProcessorTest extends TestBase {

    /**
     * @var TabularDatasetSnapshotProcessor
     */
    private $processor;


    /**
     * @var MockObject
     */
    private $datasetService;


    /**
     * @var MockObject
     */
    private $datasourceService;


    /**
     * @var MockObject
     */
    private $tableMapper;


    public function setUp(): void {
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->tableMapper = MockObjectProvider::instance()->getMockInstance(TableMapper::class);

        $this->processor = new TabularDatasetSnapshotProcessor($this->datasetService, $this->datasourceService, $this->tableMapper);
    }


    public function testOnSimpleSnapshotProcessDatasourceUpdatedWithDataProgressively() {

        $datasetInstance = new DatasetInstance(null, 1, null);

        $now = date("Y-m-d H:i:s");

        $firstDataset = [];
        for ($i = 0; $i < TabularDatasetSnapshotProcessor::DATA_LIMIT; $i++) {
            $firstDataset[] = ["title" => "Item $i", "metric" => $i + 1, "score" => $i + 1];
        }

        $firstDatasetWithDate = $firstDataset;
        for ($i = 0; $i < sizeof($firstDatasetWithDate); $i++) {
            $firstDatasetWithDate[$i]["snapshot_date"] = $now;
        }

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("score")
            ], $firstDataset), [
                $datasetInstance, [], [], 0, TabularDatasetSnapshotProcessor::DATA_LIMIT
            ]);


        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("score")
            ], [
                ["title" => "Item 11", "metric" => 11, "score" => 11],
                ["title" => "Item 12", "metric" => 12, "score" => 12],
                ["title" => "Item 13", "metric" => 13, "score" => 13],

            ]), [
                $datasetInstance, [], [], TabularDatasetSnapshotProcessor::DATA_LIMIT, TabularDatasetSnapshotProcessor::DATA_LIMIT
            ]);

        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([
            new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true),
            new Field("title", "Title", null, Field::TYPE_STRING, true),
            new Field("metric"),
            new Field("score")
        ], $firstDatasetWithDate), ["mytestsnapshot_pending", [], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[snapshot_date]]", $now)]))], 0, TabularDatasetSnapshotProcessor::DATA_LIMIT]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([
            new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true),
            new Field("title", "Title", null, Field::TYPE_STRING, true),
            new Field("metric"),
            new Field("score")
        ], [["snapshot_date" => $now, "title" => "Item 11", "metric" => 11, "score" => 11],
            ["snapshot_date" => $now, "title" => "Item 12", "metric" => 12, "score" => 12],
            ["snapshot_date" => $now, "title" => "Item 13", "metric" => 13, "score" => 13]
        ]), ["mytestsnapshot_pending", [], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[snapshot_date]]", $now)]))], TabularDatasetSnapshotProcessor::DATA_LIMIT, TabularDatasetSnapshotProcessor::DATA_LIMIT]);


        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);


        // Get a mock data source instance
        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDataSourceInstanceLatest = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDataSourceInstancePending = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);


        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestsnapshot"
        ]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstanceLatest, [
            "mytestsnapshot_latest"
        ]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstancePending, [
            "mytestsnapshot_pending"
        ]);


        // Program a mock data source on return
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSource->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table", "test"));
        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);
        $mockDataSourceInstance->returnValue("getConfig", [
            "tableName" => "test"
        ]);

        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnection);
        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);


        $mockDataSourceLatest = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSourceLatest->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table", "test_latest"));
        $mockDataSourceInstanceLatest->returnValue("returnDataSource", $mockDataSourceLatest);
        $mockDataSourceInstance->returnValue("getConfig", [
            "tableName" => "test_latest"
        ]);

        $mockAuthCredentialsLatest = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnectionLatest = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnectionLatest);
        $mockDataSourceLatest->returnValue("getAuthenticationCredentials", $mockAuthCredentialsLatest);


        $mockDataSourcePending = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSourcePending->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table", "test_pending"));
        $mockDataSourceInstancePending->returnValue("returnDataSource", $mockDataSourcePending);
        $mockDataSourceInstance->returnValue("getConfig", [
            "tableName" => "test_pending"
        ]);

        $mockAuthCredentialsPending = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnectionPending = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentialsPending->returnValue("returnDatabaseConnection", $mockDatabaseConnectionPending);
        $mockDataSourcePending->returnValue("getAuthenticationCredentials", $mockAuthCredentialsPending);


        $config = new TabularDatasetSnapshotProcessorConfiguration(["title"], [], 25, "mytestsnapshot", true, true);
        $instance = new DataProcessorInstance("no", "need", "tabulardatasetsnapshot", $config);
        $this->processor->process($instance);


        // Check that the table was created with simple snapshot_date based PK.
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $mockDataSourceInstance
        ]));


        // Check all data was updated as expected
        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true),
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("score")
            ], $firstDatasetWithDate)
        ]));


        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true),
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("score")
            ], [
                ["snapshot_date" => $now, "title" => "Item 11", "metric" => 11, "score" => 11],
                ["snapshot_date" => $now, "title" => "Item 12", "metric" => 12, "score" => 12],
                ["snapshot_date" => $now, "title" => "Item 13", "metric" => 13, "score" => 13]
            ])
        ]));


        //  Check pending datasource is updated as expected
        $this->assertTrue($mockDataSourcePending->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true),
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("score")
            ], $firstDatasetWithDate)
        ]));

        $this->assertTrue($mockDataSourcePending->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true),
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("score")
            ], [
                ["snapshot_date" => $now, "title" => "Item 11", "metric" => 11, "score" => 11],
                ["snapshot_date" => $now, "title" => "Item 12", "metric" => 12, "score" => 12],
                ["snapshot_date" => $now, "title" => "Item 13", "metric" => 13, "score" => 13]
            ])
        ]));


        // Check all latest is updated as expected
        $this->assertTrue(($mockDataSourceLatest->methodWasCalled("onInstanceDelete")));


        $this->assertTrue($mockDataSourceLatest->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("score")
            ], $firstDataset)
        ]));

        $this->assertTrue($mockDataSourceLatest->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("score")
            ], [
                ["title" => "Item 11", "metric" => 11, "score" => 11],
                ["title" => "Item 12", "metric" => 12, "score" => 12],
                ["title" => "Item 13", "metric" => 13, "score" => 13]
            ])
        ]));

    }


    public function testOnProcessSnapshotsWithTimelapseConfigurationsTableIsModifiedAndPreviousDataObtainedAndMergedIntoDataset() {

        $datasetInstance = new DatasetInstance(null, 1, null);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ], [
                ["title" => "Item 1", "metric" => 1, "score" => 1],
                ["title" => "Item 2", "metric" => 2, "score" => 2],
            ]), [
                $datasetInstance, [], [], 0, TabularDatasetSnapshotProcessor::DATA_LIMIT
            ]);


        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);


        // Get a mock data source instance
        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestsnapshot"
        ]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestsnapshot_latest"
        ]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestsnapshot_pending"
        ]);


        // Program a mock data source on return
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSource->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table", "test"));
        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);
        $mockDataSourceInstance->returnValue("getConfig", [
            "tableName" => "test"
        ]);


        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnection);
        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);


        $config = new TabularDatasetSnapshotProcessorConfiguration([
            "title"
        ], [
            new TimeLapseFieldSet([1, 7, 30], ["metric"]),
            new TimeLapseFieldSet([5, 15], ["score"])
        ], 25, "mytestsnapshot", false, true);


        $oneDayAgo = (new \DateTime())->sub(new \DateInterval("P1D"))->format("Y-m-d") . " 13:00:20";
        $sevenDaysAgo = (new \DateTime())->sub(new \DateInterval("P7D"))->format("Y-m-d") . " 04:23:48";
        $thirtyDaysAgo = (new \DateTime())->sub(new \DateInterval("P30D"))->format("Y-m-d") . " 07:15:28";
        $fiveDaysAgo = (new \DateTime())->sub(new \DateInterval("P5D"))->format("Y-m-d") . " 18:34:39";
        $fifteenDaysAgo = (new \DateTime())->sub(new \DateInterval("P15D"))->format("Y-m-d") . " 22:23:48";


        $this->tableMapper->returnValue("multiFetch", [
            $oneDayAgo . "||Item 1" => ["snapshot_date" => $oneDayAgo, "title" => "Item 1", "metric" => 11, "score" => 11],
            $sevenDaysAgo . "||Item 1" => ["snapshot_date" => $sevenDaysAgo, "title" => "Item 1", "metric" => 111, "score" => 111],
            $fifteenDaysAgo . "||Item 1" => ["snapshot_date" => $fifteenDaysAgo, "title" => "Item 1", "metric" => 1111, "score" => 1111],
            $oneDayAgo . "||Item 2" => ["snapshot_date" => $oneDayAgo, "title" => "Item 2", "metric" => 22, "score" => 22],
            $sevenDaysAgo . "||Item 2" => ["snapshot_date" => $sevenDaysAgo, "title" => "Item 2", "metric" => 222, "score" => 222],
            $fifteenDaysAgo . "||Item 2" => ["snapshot_date" => $fifteenDaysAgo, "title" => "Item 2", "metric" => 2222, "score" => 2222],
        ], [
                new TableMapping("test", [], $mockDatabaseConnection, ["snapshot_date", "title"]),
                [
                    [$oneDayAgo, "Item 1"],
                    [$sevenDaysAgo, "Item 1"],
                    [$thirtyDaysAgo, "Item 1"],
                    [$fiveDaysAgo, "Item 1"],
                    [$fifteenDaysAgo, "Item 1"],
                    [$oneDayAgo, "Item 2"],
                    [$sevenDaysAgo, "Item 2"],
                    [$thirtyDaysAgo, "Item 2"],
                    [$fiveDaysAgo, "Item 2"],
                    [$fifteenDaysAgo, "Item 2"]
                ],
                true
            ]
        );
        $now = date("Y-m-d H:i:s");

        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([new Field("snapshot_date")], [$oneDayAgo]), [
            "mytestsnapshot", [], [new TransformationInstance("filter", new FilterTransformation([new Filter("substr([[snapshot_date]], 1, 10)", substr($oneDayAgo, 0, 10), "eq")]))], 0, 1
        ]);
        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([new Field("snapshot_date")], [$sevenDaysAgo]), [
            "mytestsnapshot", [], [new TransformationInstance("filter", new FilterTransformation([new Filter("substr([[snapshot_date]], 1, 10)", substr($sevenDaysAgo, 0, 10), "eq")]))], 0, 1
        ]);
        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([new Field("snapshot_date")], [$fifteenDaysAgo]), [
            "mytestsnapshot", [], [new TransformationInstance("filter", new FilterTransformation([new Filter("substr([[snapshot_date]], 1, 10)", substr($fifteenDaysAgo, 0, 10), "eq")]))], 0, 1
        ]);
        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([new Field("snapshot_date")], [$thirtyDaysAgo]), [
            "mytestsnapshot", [], [new TransformationInstance("filter", new FilterTransformation([new Filter("substr([[snapshot_date]], 1, 10)", substr($thirtyDaysAgo, 0, 10), "eq")]))], 0, 1
        ]);
        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([new Field("snapshot_date")], [$fiveDaysAgo]), [
            "mytestsnapshot", [], [new TransformationInstance("filter", new FilterTransformation([new Filter("substr([[snapshot_date]], 1, 10)", substr($fiveDaysAgo, 0, 10), "eq")]))], 0, 1
        ]);


        $instance = new DataProcessorInstance("no", "need", "tabulardatasetsnapshot", $config);

        // Process
        $this->processor->process($instance);


        // Check that the table was created with snapshot and title as primary key
        // And timelapse fields
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $mockDataSourceInstance
        ]));


        // Check all data was updated as expected
        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true),
                new Field("title", "Title", null, Field::TYPE_STRING, true),
                new Field("metric"),
                new Field("metric_1_days_ago"),
                new Field("metric_7_days_ago"),
                new Field("metric_30_days_ago"),
                new Field("score"),
                new Field("score_5_days_ago"),
                new Field("score_15_days_ago")
            ], [
                ["snapshot_date" => $now, "title" => "Item 1", "metric" => 1,
                    "metric_1_days_ago" => 11, "metric_7_days_ago" => 111, "metric_30_days_ago" => null,
                    "score" => 1, "score_5_days_ago" => null, "score_15_days_ago" => 1111],
                ["snapshot_date" => $now, "title" => "Item 2", "metric" => 2,
                    "metric_1_days_ago" => 22, "metric_7_days_ago" => 222, "metric_30_days_ago" => null,
                    "score" => 2, "score_5_days_ago" => null, "score_15_days_ago" => 2222],
            ])
        ]));


    }

    public function testOnSimpleSnapshotProcessDatasourceUpdatedWithDataProgressivelyWhenNoKeyFieldsSuppliedByUser() {

        $datasetInstance = new DatasetInstance(null, 1, null);

        $now = date("Y-m-d H:i:s");

        $firstDataset = [];
        for ($i = 0; $i < TabularDatasetSnapshotProcessor::DATA_LIMIT; $i++) {
            $firstDataset[] = ["title" => "Item $i", "metric" => $i + 1, "score" => $i + 1];
        }

        $firstDatasetPlus = $firstDataset;
        for ($i = 0; $i < sizeof($firstDatasetPlus); $i++) {
            $firstDatasetPlus[$i]["snapshot_date"] = $now;
            $firstDatasetPlus[$i]["snapshot_item_id"] = $i;
        }


        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ], $firstDataset), [
                $datasetInstance, [], [], 0, TabularDatasetSnapshotProcessor::DATA_LIMIT
            ]);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("title"),
                new Field("metric"),
                new Field("score")], []), [
                $datasetInstance, [], [], TabularDatasetSnapshotProcessor::DATA_LIMIT, TabularDatasetSnapshotProcessor::DATA_LIMIT
            ]);


        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);


        // Get a mock data source instance
        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDataSourceInstanceLatest = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDataSourceInstancePending = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);


        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestsnapshot"
        ]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstanceLatest, [
            "mytestsnapshot_latest"
        ]);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstancePending, [
            "mytestsnapshot_pending"
        ]);


        // Program a mock data source on return
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSource->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table", "test"));
        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);
        $mockDataSourceInstance->returnValue("getConfig", [
            "tableName" => "test"
        ]);

        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnection);
        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);

        $expectedUpdate = new ArrayTabularDataset([
            new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true),
            new Field("snapshot_item_id", "Snapshot Item Id", null, Field::TYPE_INTEGER, true),
            new Field("title"),
            new Field("metric"),
            new Field("score")], $firstDatasetPlus);

        $config = new TabularDatasetSnapshotProcessorConfiguration([], [], 25, "mytestsnapshot", false, true);
        $instance = new DataProcessorInstance("no", "need", "tabulardatasetsnapshot", $config);
        $this->processor->process($instance);


        // Check that the table was created with simple snapshot_date based PK.
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $mockDataSourceInstance
        ]));

        // Check all data was updated as expected
        $this->assertTrue($mockDataSource->methodWasCalled("update", [$expectedUpdate]));

    }
}