<?php

namespace Kinintel\Test\Services\DataProcessor\DatasetSnapshot;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\TableMapper\Mapper\TableMapper;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\TabularSnapshotDataSource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\DataProcessor\DatasetSnapshot\TabularDatasetIncrementalSnapshotProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetIncrementalSnapshotProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseExpression;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class TabularDatasetIncrementalSnapshotProcessorTest extends TestBase {

    /**
     * @var TabularDatasetIncrementalSnapshotProcessor
     */
    private $snapshotProcessor;

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

        $this->snapshotProcessor = new TabularDatasetIncrementalSnapshotProcessor($this->datasetService, $this->datasourceService, $this->tableMapper);

    }


    public function testCanProcessBrandNewSnapshot() {


        $this->datasourceService->throwException("getEvaluatedDataSource", new ObjectNotFoundException(DatasourceInstance::class, "incrementalsnap"), [
            "incrementalsnap", [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MAX, "id", null, "snapshotLastValue")]))
            ]
        ]);

        $this->datasourceService->throwException("getDataSourceInstanceByKey", new ObjectNotFoundException(DatasourceInstance::class, "incrementalsnap"), [
            "incrementalsnap"
        ]);


        $fields = [
            new Field("id", "Id", null, Field::TYPE_INTEGER),
            new Field("name"),
            new Field("phone")
        ];

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            new ArrayTabularDataset($fields, [
                [
                    "id" => 1,
                    "name" => "Mr Blobby",
                    "phone" => "012865 787879"
                ],
                [
                    "id" => 2,
                    "name" => "Simon",
                    "phone" => "07676 123456"
                ],
                [
                    "id" => 3,
                    "name" => "James",
                    "phone" => "01223 333333"
                ]
            ]), [
                99, [], [], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("simpleincremental", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration(99, "incrementalsnap", "id"));


        $snapshotFields = array_merge([
            new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true)
        ], $fields);

        $expectedNewDatasourceInstance = new DatasourceInstance("incrementalsnap", "Simple Incremental", "snapshot",
            new ManagedTableSQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "snapshot.incrementalsnap", null,
                $snapshotFields, true
            ),
            "test"
        );


        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = MockObjectProvider::instance()->getMockInstance(TabularSnapshotDataSource::class);
        $datasourceInstance->returnValue("returnDataSource", $datasource);
        $this->datasourceService->returnValue("saveDataSourceInstance", $datasourceInstance, [$expectedNewDatasourceInstance]);


        // Process the instance
        $this->snapshotProcessor->process($dataProcessorInstance);


        // Check datasource instance was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", $expectedNewDatasourceInstance));

        // Check data was inserted into datasource with all fields hashed together.
        $this->assertTrue($datasource->methodWasCalled("update", [new ArrayTabularDataset($snapshotFields, [
            [
                "snapshot_item_id" => hash("sha512", "1Mr Blobby012865 787879"),
                "id" => 1,
                "name" => "Mr Blobby",
                "phone" => "012865 787879"
            ],
            [
                "snapshot_item_id" => hash("sha512", "2Simon07676 123456"),
                "id" => 2,
                "name" => "Simon",
                "phone" => "07676 123456"
            ],
            [
                "snapshot_item_id" => hash("sha512", "3James01223 333333"),
                "id" => 3,
                "name" => "James",
                "phone" => "01223 333333"
            ]
        ]), UpdatableDatasource::UPDATE_MODE_REPLACE]));


    }


    public function testCanProcessIncrementalUpdatesToExistingSnapshot() {


        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([
            new Field("snapshotLastValue")
        ], [["snapshotLastValue" => 25]]), [
            "incrementalsnap", [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MAX, "id", null, "snapshotLastValue")]))
            ]
        ]);

        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = MockObjectProvider::instance()->getMockInstance(TabularSnapshotDataSource::class);
        $instanceConfig = MockObjectProvider::instance()->getMockInstance(ManagedTableSQLDatabaseDatasourceConfig::class);
        $datasourceInstance->returnValue("returnDataSource", $datasource);
        $datasourceInstance->returnValue("getConfig", $instanceConfig);


        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasourceInstance, [
            "incrementalsnap"
        ]);


        $fields = [
            new Field("id", "Id", null, Field::TYPE_INTEGER),
            new Field("name"),
            new Field("phone")
        ];

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            new ArrayTabularDataset($fields, [
                [
                    "id" => 1,
                    "name" => "Mr Blobby",
                    "phone" => "012865 787879"
                ],
                [
                    "id" => 2,
                    "name" => "Simon",
                    "phone" => "07676 123456"
                ],
                [
                    "id" => 3,
                    "name" => "James",
                    "phone" => "01223 333333"
                ]
            ]), [
                99, [], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[id]]", "25", Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO)]))], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("simpleincremental", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration(99, "incrementalsnap", "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER_OR_EQUAL));


        $snapshotFields = array_merge([
            new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true)
        ], $fields);


        $this->datasourceService->returnValue("saveDataSourceInstance", $datasourceInstance, [$datasourceInstance]);


        // Process the instance
        $this->snapshotProcessor->process($dataProcessorInstance);


        // Check datasource instance was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", $datasourceInstance));

        // Check data was inserted into datasource with all fields hashed together.
        $this->assertTrue($datasource->methodWasCalled("update", [new ArrayTabularDataset($snapshotFields, [
            [
                "snapshot_item_id" => hash("sha512", "1Mr Blobby012865 787879"),
                "id" => 1,
                "name" => "Mr Blobby",
                "phone" => "012865 787879"
            ],
            [
                "snapshot_item_id" => hash("sha512", "2Simon07676 123456"),
                "id" => 2,
                "name" => "Simon",
                "phone" => "07676 123456"
            ],
            [
                "snapshot_item_id" => hash("sha512", "3James01223 333333"),
                "id" => 3,
                "name" => "James",
                "phone" => "01223 333333"
            ]
        ]), UpdatableDatasource::UPDATE_MODE_REPLACE]));


    }


    public function testCanProcessIncrementalUpdatesToExistingSnapshotWithLesserRuleInPlace() {


        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([
            new Field("snapshotLastValue")
        ], [["snapshotLastValue" => 25]]), [
            "incrementalsnap", [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MIN, "id", null, "snapshotLastValue")]))
            ]
        ]);

        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = MockObjectProvider::instance()->getMockInstance(TabularSnapshotDataSource::class);
        $instanceConfig = MockObjectProvider::instance()->getMockInstance(ManagedTableSQLDatabaseDatasourceConfig::class);
        $datasourceInstance->returnValue("returnDataSource", $datasource);
        $datasourceInstance->returnValue("getConfig", $instanceConfig);


        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasourceInstance, [
            "incrementalsnap"
        ]);


        $fields = [
            new Field("id", "Id", null, Field::TYPE_INTEGER),
            new Field("name"),
            new Field("phone")
        ];

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            new ArrayTabularDataset($fields, [
                [
                    "id" => 1,
                    "name" => "Mr Blobby",
                    "phone" => "012865 787879"
                ],
                [
                    "id" => 2,
                    "name" => "Simon",
                    "phone" => "07676 123456"
                ],
                [
                    "id" => 3,
                    "name" => "James",
                    "phone" => "01223 333333"
                ]
            ]), [
                99, [], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[id]]", "25", Filter::FILTER_TYPE_LESS_THAN)]))], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("simpleincremental", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration(99, "incrementalsnap", "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_LESSER));


        $snapshotFields = array_merge([
            new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true)
        ], $fields);


        $this->datasourceService->returnValue("saveDataSourceInstance", $datasourceInstance, [$datasourceInstance]);


        // Process the instance
        $this->snapshotProcessor->process($dataProcessorInstance);


        // Check datasource instance was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", $datasourceInstance));

        // Check data was inserted into datasource with all fields hashed together.
        $this->assertTrue($datasource->methodWasCalled("update", [new ArrayTabularDataset($snapshotFields, [
            [
                "snapshot_item_id" => hash("sha512", "1Mr Blobby012865 787879"),
                "id" => 1,
                "name" => "Mr Blobby",
                "phone" => "012865 787879"
            ],
            [
                "snapshot_item_id" => hash("sha512", "2Simon07676 123456"),
                "id" => 2,
                "name" => "Simon",
                "phone" => "07676 123456"
            ],
            [
                "snapshot_item_id" => hash("sha512", "3James01223 333333"),
                "id" => 3,
                "name" => "James",
                "phone" => "01223 333333"
            ]
        ]), UpdatableDatasource::UPDATE_MODE_REPLACE]));


    }


    public function testCanProcessIncrementalUpdatesToExistingSnapshotWithExplicitKeyFields() {


        $this->datasourceService->returnValue("getEvaluatedDataSource", new ArrayTabularDataset([
            new Field("snapshotLastValue")
        ], [["snapshotLastValue" => 25]]), [
            "incrementalsnap", [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MAX, "id", null, "snapshotLastValue")]))
            ]
        ]);

        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = MockObjectProvider::instance()->getMockInstance(TabularSnapshotDataSource::class);
        $instanceConfig = MockObjectProvider::instance()->getMockInstance(ManagedTableSQLDatabaseDatasourceConfig::class);
        $datasourceInstance->returnValue("returnDataSource", $datasource);
        $datasourceInstance->returnValue("getConfig", $instanceConfig);


        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasourceInstance, [
            "incrementalsnap"
        ]);


        $fields = [
            new Field("id", "Id", null, Field::TYPE_INTEGER),
            new Field("name"),
            new Field("phone")
        ];

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            new ArrayTabularDataset($fields, [
                [
                    "id" => 1,
                    "name" => "Mr Blobby",
                    "phone" => "012865 787879"
                ],
                [
                    "id" => 2,
                    "name" => "Simon",
                    "phone" => "07676 123456"
                ],
                [
                    "id" => 3,
                    "name" => "James",
                    "phone" => "01223 333333"
                ]
            ]), [
                99, [], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[id]]", "25", Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO)]))], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("simpleincremental", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration(99, "incrementalsnap", "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER_OR_EQUAL, [
                "id", "name"
            ]));


        $snapshotFields = array_merge([
            new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true)
        ], $fields);


        $this->datasourceService->returnValue("saveDataSourceInstance", $datasourceInstance, [$datasourceInstance]);


        // Process the instance
        $this->snapshotProcessor->process($dataProcessorInstance);


        // Check datasource instance was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", $datasourceInstance));

        // Check data was inserted into datasource with all fields hashed together.
        $this->assertTrue($datasource->methodWasCalled("update", [new ArrayTabularDataset($snapshotFields, [
            [
                "snapshot_item_id" => hash("sha512", "1Mr Blobby"),
                "id" => 1,
                "name" => "Mr Blobby",
                "phone" => "012865 787879"
            ],
            [
                "snapshot_item_id" => hash("sha512", "2Simon"),
                "id" => 2,
                "name" => "Simon",
                "phone" => "07676 123456"
            ],
            [
                "snapshot_item_id" => hash("sha512", "3James"),
                "id" => 3,
                "name" => "James",
                "phone" => "01223 333333"
            ]
        ]), UpdatableDatasource::UPDATE_MODE_REPLACE]));


    }


}