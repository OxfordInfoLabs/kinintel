<?php

namespace Kinintel\Test\Services\DataProcessor\DatasetSnapshot;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\TableMapper\Mapper\TableMapper;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
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
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;
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


        $datasetInstance = new DatasetInstance(null, 1, "testProject");
        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [99]);


        $this->datasourceService->throwException("getEvaluatedDataSourceByInstanceKey", new ObjectNotFoundException(DatasourceInstance::class, "incrementalsnap"), [
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


        $dataProcessorInstance = new DataProcessorInstance("incrementalsnap", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration([], "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER, [], [new Index(["name", "phone"])]),null,null,"DatasetInstance",99);


        $snapshotFields = array_merge([
            new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true)
        ], $fields);

        $expectedNewDatasourceInstance = new DatasourceInstance("incrementalsnap", "Simple Incremental", "snapshot",
            new ManagedTableSQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "snapshot.incrementalsnap", null,
                $snapshotFields, true, [new Index(["name", "phone"])]
            ),
            "test"
        );
        $expectedNewDatasourceInstance->setAccountId(1);
        $expectedNewDatasourceInstance->setProjectKey("testProject");


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


    public function testDataIsReadAccordingToReadChunkSizeWithPagingIfSet() {

        $datasetInstance = new DatasetInstance(null, 1, "testProject");
        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [99]);


        $this->datasourceService->throwException("getEvaluatedDataSourceByInstanceKey", new ObjectNotFoundException(DatasourceInstance::class, "incrementalsnap"), [
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
                99, [], [], 0, 3
            ]);


        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            new ArrayTabularDataset($fields, [
                [
                    "id" => 4,
                    "name" => "Mr Biggy",
                    "phone" => "012865 787879"
                ],
                [
                    "id" => 5,
                    "name" => "David",
                    "phone" => "07676 123456"
                ]
            ]), [
                99, [], [], 3, 3
            ]);


        $dataProcessorInstance = new DataProcessorInstance("incrementalsnap", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration([], "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER, [], [], 3),null,null,"DatasetInstance",99);


        $snapshotFields = array_merge([
            new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true)
        ], $fields);

        $expectedNewDatasourceInstance = new DatasourceInstance("incrementalsnap", "Simple Incremental", "snapshot",
            new ManagedTableSQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "snapshot.incrementalsnap", null,
                $snapshotFields, true
            ),
            "test"
        );
        $expectedNewDatasourceInstance->setAccountId(1);
        $expectedNewDatasourceInstance->setProjectKey("testProject");


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


        $this->assertTrue($datasource->methodWasCalled("update", [new ArrayTabularDataset($snapshotFields, [
            [
                "snapshot_item_id" => hash("sha512", "4Mr Biggy012865 787879"),
                "id" => 4,
                "name" => "Mr Biggy",
                "phone" => "012865 787879"
            ],
            [
                "snapshot_item_id" => hash("sha512", "5David07676 123456"),
                "id" => 5,
                "name" => "David",
                "phone" => "07676 123456"
            ]
        ]), UpdatableDatasource::UPDATE_MODE_REPLACE]));

    }


    public function testCanProcessIncrementalUpdatesToExistingSnapshot() {

        $datasetInstance = new DatasetInstance(null, 1, "testProject");
        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [99]);


        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", new ArrayTabularDataset([
            new Field("snapshotLastValue")
        ], [["snapshotLastValue" => 25]]), [
            "incrementalsnap", [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MAX, "id", null, "snapshotLastValue")]))
            ]
        ]);

        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = MockObjectProvider::instance()->getMockInstance(TabularSnapshotDataSource::class);
        $instanceConfig = [];
        $datasourceInstance->returnValue("returnDataSource", $datasource);
        $datasourceInstance->returnValue("getConfig", $instanceConfig);
        $datasourceInstance->returnValue("getAccountId", 1);
        $datasourceInstance->returnValue("getProjectKey", "testProject");


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
                99, [], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[id]]", "25", FilterType::gte)]))], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("incrementalsnap", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration([], "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER_OR_EQUAL),null,null,"DatasetInstance",99);


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


        $datasetInstance = new DatasetInstance(null, 1, "testProject");
        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [99]);


        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", new ArrayTabularDataset([
            new Field("snapshotLastValue")
        ], [["snapshotLastValue" => 25]]), [
            "incrementalsnap", [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MIN, "id", null, "snapshotLastValue")]))
            ]
        ]);

        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = MockObjectProvider::instance()->getMockInstance(TabularSnapshotDataSource::class);
        $instanceConfig = [];
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
                99, [], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[id]]", "25", FilterType::lt)]))], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("incrementalsnap", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration([], "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_LESSER),null,null,"DatasetInstance",99);


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


    public function testWhenParameterValuesConfiguredTheseArePassedThroughToDataset(){

        $datasetInstance = new DatasetInstance(null, 1, "testProject");
        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [99]);


        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", new ArrayTabularDataset([
            new Field("snapshotLastValue")
        ], [["snapshotLastValue" => 25]]), [
            "incrementalsnap", [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MIN, "id", null, "snapshotLastValue")]))
            ]
        ]);

        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = MockObjectProvider::instance()->getMockInstance(TabularSnapshotDataSource::class);
        $instanceConfig = [];
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
                99, ["test" => "Hello", "test2" => 44], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[id]]", "25", FilterType::lt)]))], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("incrementalsnap", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration(["test" => "Hello", "test2" => 44], "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_LESSER)
            ,null,null,"DatasetInstance",99);


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

        $datasetInstance = new DatasetInstance(null, 1, "testProject");
        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [99]);

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", new ArrayTabularDataset([
            new Field("snapshotLastValue")
        ], [["snapshotLastValue" => 25]]), [
            "incrementalsnap", [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_MAX, "id", null, "snapshotLastValue")]))
            ]
        ]);

        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = MockObjectProvider::instance()->getMockInstance(TabularSnapshotDataSource::class);
        $instanceConfig = [];
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
                99, [], [new TransformationInstance("filter", new FilterTransformation([new Filter("[[id]]", "25", FilterType::gte)]))], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("incrementalsnap", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration([], "id", TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER_OR_EQUAL, [
                "id", "name"
            ]),null,null,"DatasetInstance",99);


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


    public function testIfSourceDatasetForSnapshotContainsASnapshotItemIdColumnOrSnapshotDateAlreadyTheyAreOmittedFromNewSnapshot() {


        $datasetInstance = new DatasetInstance(null, 1, "testProject");
        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [99]);


        $this->datasourceService->throwException("getEvaluatedDataSourceByInstanceKey", new ObjectNotFoundException(DatasourceInstance::class, "incrementalsnap"), [
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
            new Field("phone"),
            new Field("snapshot_item_id"),
            new Field("snapshot_date")
        ];

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            new ArrayTabularDataset($fields, [
                [
                    "id" => 1,
                    "name" => "Mr Blobby",
                    "phone" => "012865 787879",
                    "snapshot_item_id" => "ABC",
                    "snapshot_date" => "2012-01-01 10:33:44"
                ],
                [
                    "id" => 2,
                    "name" => "Simon",
                    "phone" => "07676 123456",
                    "snapshot_item_id" => "DEF",
                    "snapshot_date" => "2013-01-01 10:33:44"
                ],
                [
                    "id" => 3,
                    "name" => "James",
                    "phone" => "01223 333333",
                    "snapshot_item_id" => "GHI",
                    "snapshot_date" => "2014-01-01 10:33:44"
                ]
            ]), [
                99, [], [], 0, 50000
            ]);


        $dataProcessorInstance = new DataProcessorInstance("incrementalsnap", "Simple Incremental", "tabulardatasetincrementalsnapshot",
            new TabularDatasetIncrementalSnapshotProcessorConfiguration([], "id"),null,null,"DatasetInstance",99);


        $snapshotFields = array_merge([
            new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true)
        ], $fields);
        array_pop($snapshotFields);
        array_pop($snapshotFields);

        $expectedNewDatasourceInstance = new DatasourceInstance("incrementalsnap", "Simple Incremental", "snapshot",
            new ManagedTableSQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "snapshot.incrementalsnap", null,
                $snapshotFields, true
            ),
            "test"
        );
        $expectedNewDatasourceInstance->setAccountId(1);
        $expectedNewDatasourceInstance->setProjectKey("testProject");


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
                "id" => 1,
                "name" => "Mr Blobby",
                "phone" => "012865 787879",
                "snapshot_item_id" => hash("sha512", "1Mr Blobby012865 787879"),
            ],
            [
                "id" => 2,
                "name" => "Simon",
                "phone" => "07676 123456",
                "snapshot_item_id" => hash("sha512", "2Simon07676 123456"),
            ],
            [
                "id" => 3,
                "name" => "James",
                "phone" => "01223 333333",
                "snapshot_item_id" => hash("sha512", "3James01223 333333"),
            ]
        ]), UpdatableDatasource::UPDATE_MODE_REPLACE]));


    }

    public function testGeneratedDatasourceIsRemovedOnInstanceDelete() {

        $instance = new DataProcessorInstance("onetogo", "One to Go", "test");

        $this->snapshotProcessor->onInstanceDelete($instance);

        // Check all three deletes are attempted
        $this->assertTrue($this->datasourceService->methodWasCalled("removeDatasourceInstance", ["onetogo"]));

        // Handle exceptions as well
        $this->datasourceService->throwException("removeDatasourceInstance",new ObjectNotFoundException("TEST",1),["onetogo"]);

        // Check for silent failures
        $this->snapshotProcessor->onInstanceDelete($instance);


    }

}