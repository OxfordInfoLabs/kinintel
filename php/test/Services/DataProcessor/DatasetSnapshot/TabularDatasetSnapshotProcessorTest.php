<?php


namespace Kinintel\Test\Services\DataProcessor\DatasetSnapshot;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\TableMapper\Mapper\TableMapper;
use Kinikit\Persistence\TableMapper\Mapper\TableMapping;
use Kinintel\Controllers\Account\Datasource;
use Kinintel\Controllers\API\Dataset;
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

    public function testOnProcessDatasourceCreatedForAccountWithSnapshotKeyIfNotYetCreatedAndTableStructureModified() {

        $datasetInstance = new DatasetInstance(null, 1, null);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ], [
            ]), [
                $datasetInstance, [], 0, 10
            ]);

        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);


        // Ensure we get object not found when obtaining existing datasource
        $this->datasourceService->throwException("getDataSourceInstanceByKey", new ObjectNotFoundException(DatasourceInstance::class, "mytestsnapshot"), [
            "mytestsnapshot"
        ]);


        $expectedDatasourceInstance = new DatasourceInstance("mytestsnapshot", "mytestsnapshot", "sqldatabase", [
            "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
            "tableName" => "mytestsnapshot"
        ], "dataset_snapshot");
        $expectedDatasourceInstance->setAccountId(1);


        // Get a mock data source instace
        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $this->datasourceService->returnValue("saveDataSourceInstance", $mockDataSourceInstance, [
            $expectedDatasourceInstance
        ]);


        // Program a mock data source on return
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);

        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnection);
        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);


        $config = new TabularDatasetSnapshotProcessorConfiguration([], [], 25, "mytestsnapshot");
        $this->processor->process($config);


        // Check that the datasource instance was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $expectedDatasourceInstance
        ]));


        // Check that the table was created with simple snapshot_date based PK.
        $this->assertTrue($mockDataSource->methodWasCalled("modifyTableStructure", [
            [
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE),
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ],
            ["snapshot_date"]
        ]));

    }


    public function testOnSimpleSnapshotProcessDatasourceUpdatedWithDataProgressively() {

        $datasetInstance = new DatasetInstance(null, 1, null);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ], [
                ["title" => "Item 1", "metric" => 1, "score" => 1],
                ["title" => "Item 2", "metric" => 2, "score" => 2],
                ["title" => "Item 3", "metric" => 3, "score" => 3],
                ["title" => "Item 4", "metric" => 4, "score" => 4],
                ["title" => "Item 5", "metric" => 5, "score" => 5],
                ["title" => "Item 6", "metric" => 6, "score" => 6],
                ["title" => "Item 7", "metric" => 7, "score" => 7],
                ["title" => "Item 8", "metric" => 8, "score" => 8],
                ["title" => "Item 9", "metric" => 9, "score" => 9],
                ["title" => "Item 10", "metric" => 10, "score" => 10]
            ]), [
                $datasetInstance, [], 0, 10
            ]);


        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ], [
                ["title" => "Item 11", "metric" => 11, "score" => 11],
                ["title" => "Item 12", "metric" => 12, "score" => 12],
                ["title" => "Item 13", "metric" => 13, "score" => 13],

            ]), [
                $datasetInstance, [], 10, 10
            ]);


        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);


        // Get a mock data source instace
        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestsnapshot"
        ]);


        // Program a mock data source on return
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);

        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnection);
        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);


        $config = new TabularDatasetSnapshotProcessorConfiguration([], [], 25, "mytestsnapshot");
        $this->processor->process($config);


        // Check that the table was created with simple snapshot_date based PK.
        $this->assertTrue($mockDataSource->methodWasCalled("modifyTableStructure", [
            [
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE),
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ],
            ["snapshot_date"]
        ]));


        $now = date("Y-m-d");

        // Check all data was updated as expected
        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE),
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ], [
                ["snapshot_date" => $now, "title" => "Item 1", "metric" => 1, "score" => 1],
                ["snapshot_date" => $now, "title" => "Item 2", "metric" => 2, "score" => 2],
                ["snapshot_date" => $now, "title" => "Item 3", "metric" => 3, "score" => 3],
                ["snapshot_date" => $now, "title" => "Item 4", "metric" => 4, "score" => 4],
                ["snapshot_date" => $now, "title" => "Item 5", "metric" => 5, "score" => 5],
                ["snapshot_date" => $now, "title" => "Item 6", "metric" => 6, "score" => 6],
                ["snapshot_date" => $now, "title" => "Item 7", "metric" => 7, "score" => 7],
                ["snapshot_date" => $now, "title" => "Item 8", "metric" => 8, "score" => 8],
                ["snapshot_date" => $now, "title" => "Item 9", "metric" => 9, "score" => 9],
                ["snapshot_date" => $now, "title" => "Item 10", "metric" => 10, "score" => 10]
            ])
        ]));


        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE),
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ], [
                ["snapshot_date" => $now, "title" => "Item 11", "metric" => 11, "score" => 11],
                ["snapshot_date" => $now, "title" => "Item 12", "metric" => 12, "score" => 12],
                ["snapshot_date" => $now, "title" => "Item 13", "metric" => 13, "score" => 13]
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
                $datasetInstance, [], 0, 10
            ]);


        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);


        // Get a mock data source instace
        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestsnapshot"
        ]);


        // Program a mock data source on return
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);


        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnection);
        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);


        $config = new TabularDatasetSnapshotProcessorConfiguration([
            "title"
        ], [
            new TimeLapseFieldSet([1, 7, 30], ["metric"]),
            new TimeLapseFieldSet([5, 15], ["score"])
        ], 25, "mytestsnapshot");


        $oneDayAgo = (new \DateTime())->sub(new \DateInterval("P1D"))->format("Y-m-d");
        $sevenDaysAgo = (new \DateTime())->sub(new \DateInterval("P7D"))->format("Y-m-d");
        $thirtyDaysAgo = (new \DateTime())->sub(new \DateInterval("P30D"))->format("Y-m-d");
        $fiveDaysAgo = (new \DateTime())->sub(new \DateInterval("P5D"))->format("Y-m-d");
        $fifteenDaysAgo = (new \DateTime())->sub(new \DateInterval("P15D"))->format("Y-m-d");


        $this->tableMapper->returnValue("multiFetch", [
            $oneDayAgo . "||Item 1" => ["snapshot_date" => $oneDayAgo, "title" => "Item 1", "metric" => 11, "score" => 11],
            $sevenDaysAgo . "||Item 1" => ["snapshot_date" => $sevenDaysAgo, "title" => "Item 1", "metric" => 111, "score" => 111],
            $fifteenDaysAgo . "||Item 1" => ["snapshot_date" => $fifteenDaysAgo, "title" => "Item 1", "metric" => 1111, "score" => 1111],
            $oneDayAgo . "||Item 2" => ["snapshot_date" => $oneDayAgo, "title" => "Item 2", "metric" => 22, "score" => 22],
            $sevenDaysAgo . "||Item 2" => ["snapshot_date" => $sevenDaysAgo, "title" => "Item 2", "metric" => 222, "score" => 222],
            $fifteenDaysAgo . "||Item 2" => ["snapshot_date" => $fifteenDaysAgo, "title" => "Item 2", "metric" => 2222, "score" => 2222],
        ], [
                new TableMapping("mytestsnapshot", [], $mockDatabaseConnection, ["snapshot_date", "title"]),
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


        // Process
        $this->processor->process($config);


        // Check that the table was created with snapshot and title as primary key
        // And timelapse fields
        $this->assertTrue($mockDataSource->methodWasCalled("modifyTableStructure", [
            [
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE),
                new Field("title"),
                new Field("metric"),
                new Field("metric_1_days_ago"),
                new Field("metric_7_days_ago"),
                new Field("metric_30_days_ago"),
                new Field("score"),
                new Field("score_5_days_ago"),
                new Field("score_15_days_ago")
            ],
            ["snapshot_date", "title"]
        ]));


        $now = date("Y-m-d");

        // Check all data was updated as expected
        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE),
                new Field("title"),
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


}