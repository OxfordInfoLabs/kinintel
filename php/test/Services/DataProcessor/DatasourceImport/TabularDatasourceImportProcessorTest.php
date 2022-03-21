<?php


namespace Kinintel\Services\DataProcessor\DatasourceImport;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\UnsupportedDatasetException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceImportProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TargetDatasource;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TargetField;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class TabularDatasourceImportProcessorTest extends TestBase {

    /**
     * @var TabularDatasourceImportProcessor
     */
    private $processor;


    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var MockObject
     */
    private $datasetService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->processor = new TabularDatasourceImportProcessor($this->datasourceService, $this->datasetService);

    }


    public function testUnsupportedDatasetExceptionRaisedIfNonTabularDatasetReturnedFromSourceDataset() {

        $mockSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockSourceInstance->returnValue("returnDataSource", $mockSource);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);

        $this->datasourceService->returnValue("getEvaluatedDataSource", $dataSet, [
            "source", null, null, 0, 500
        ]);

        $mockTargetInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockTarget = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $mockTargetInstance->returnValue("returnDataSource", $mockTarget);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockTargetInstance, [
            "target"
        ]);

        $config = new TabularDatasourceImportProcessorConfiguration("source", [
            new TargetDatasource("target")
        ]);

        try {
            $this->processor->process($config);
            $this->fail("Should have thrown here");
        } catch (UnsupportedDatasetException $e) {
            $this->assertTrue(true);
        }
    }


    public function testIfNonUpdatableDatasourceSuppliedAsTargetDatasourceExceptionRaised() {

        $mockSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockSourceInstance->returnValue("returnDataSource", $mockSource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockSourceInstance, [
            "source"
        ]);


        $mockTargetInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockTarget = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockTargetInstance->returnValue("returnDataSource", $mockTarget);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockTargetInstance, [
            "target"
        ]);


        $config = new TabularDatasourceImportProcessorConfiguration("source", [
            new TargetDatasource("target")
        ]);

        try {
            $this->processor->process($config);
            $this->fail("Should have thrown here");
        } catch (DatasourceNotUpdatableException $e) {
            $this->assertTrue(true);
        }
    }


    public function testSimpleSourceAndTargetImportResultsInAReplaceUpdateOnTargetDataset() {

        $mockSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockSourceInstance->returnValue("returnDataSource", $mockSource);

        $dataSet = new ArrayTabularDataset([new Field("bong")], [
            [
                "bong" => "bing"
            ],
            [
                "bong" => "bong"
            ]
        ]);

        $this->datasourceService->returnValue("getEvaluatedDataSource", $dataSet, [
            "source", null, null, 0, 500
        ]);


        $mockTargetInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockTarget = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $mockTargetInstance->returnValue("returnDataSource", $mockTarget);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockTargetInstance, [
            "target"
        ]);

        $config = new TabularDatasourceImportProcessorConfiguration("source", [
            new TargetDatasource("target")
        ]);

        $this->processor->process($config);


        $this->assertTrue($mockTarget->methodWasCalled("update", [
            new ArrayTabularDataset([new Field("bong")], [
                [
                    "bong" => "bing"
                ],
                [
                    "bong" => "bong"
                ]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

    }


    public function testMultipleSourceAndTargetImportResultsInAReplaceUpdateOnTargetDatasetForAllSources() {

        $mockFirstSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockFirstSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockFirstSourceInstance->returnValue("returnDataSource", $mockFirstSource);


        $dataSet1 = new ArrayTabularDataset([new Field("bong")], [
            [
                "bong" => "bing"
            ],
            [
                "bong" => "bong"
            ]
        ]);

        $this->datasourceService->returnValue("getEvaluatedDataSource", $dataSet1, [
            "source1", null, null, 0, 500
        ]);


        $mockSecondSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockSecondSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockSecondSourceInstance->returnValue("returnDataSource", $mockSecondSource);


        $dataSet2 = new ArrayTabularDataset([new Field("bong")], [
            [
                "bong" => "hello"
            ],
            [
                "bong" => "world"
            ]
        ]);

        $this->datasourceService->returnValue("getEvaluatedDataSource", $dataSet2, [
            "source2", null, null, 0, 500
        ]);


        $mockTargetInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockTarget = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $mockTargetInstance->returnValue("returnDataSource", $mockTarget);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockTargetInstance, [
            "target"
        ]);

        $config = new TabularDatasourceImportProcessorConfiguration(null, [
            new TargetDatasource("target")
        ], ["source1", "source2"]);

        $this->processor->process($config);

        $this->assertTrue($mockTarget->methodWasCalled("update", [
            new ArrayTabularDataset([new Field("bong")], [
                [
                    "bong" => "bing"
                ],
                [
                    "bong" => "bong"
                ]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

        $this->assertTrue($mockTarget->methodWasCalled("update", [
            new ArrayTabularDataset([new Field("bong")], [
                [
                    "bong" => "hello"
                ],
                [
                    "bong" => "world"
                ]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

    }

    public function testExplicitSourceDatasetInstanceAndTargetImportResultsInAReplaceUpdateOnTargetDataset() {

        $mockSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);

        $dataSet = new ArrayTabularDataset([new Field("bong")], [
            [
                "bong" => "bing"
            ],
            [
                "bong" => "bong"
            ]
        ]);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance", $dataSet, [
            $mockSourceInstance, null, null, 0, 500
        ]);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance", new ArrayTabularDataset([], []), [
            $mockSourceInstance, null, null, 500, 500
        ]);

        $mockTargetInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockTarget = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $mockTargetInstance->returnValue("returnDataSource", $mockTarget);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockTargetInstance, [
            "target"
        ]);

        $config = new TabularDatasourceImportProcessorConfiguration(null, [
            new TargetDatasource("target")
        ], [], $mockSourceInstance);

        $this->processor->process($config);

        $this->assertTrue($mockTarget->methodWasCalled("update", [
            new ArrayTabularDataset([new Field("bong")], [
                [
                    "bong" => "bing"
                ],
                [
                    "bong" => "bong"
                ]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

    }


    public function testChunkSizeIsObservedAndMultipleCallsMadeIfNecessary() {

        $mockSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockSourceInstance->returnValue("returnDataSource", $mockSource);


        $dataSet = new ArrayTabularDataset([new Field("bong")], [
            [
                "bong" => "bing"
            ],
            [
                "bong" => "bong"
            ]
        ]);

        $this->datasourceService->returnValue("getEvaluatedDataSource", $dataSet, [
            "source", null, null, 0, 1
        ]);

        $mockTargetInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockTarget = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $mockTargetInstance->returnValue("returnDataSource", $mockTarget);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockTargetInstance, [
            "target"
        ]);

        $config = new TabularDatasourceImportProcessorConfiguration("source", [
            new TargetDatasource("target")
        ], [], null, 1);


        $processor = new TabularDatasourceImportProcessor($this->datasourceService, $this->datasetService);
        $processor->process($config);

        // Expect 2 independent calls
        $this->assertTrue($mockTarget->methodWasCalled("update", [
            new ArrayTabularDataset([new Field("bong")], [
                [
                    "bong" => "bing"
                ]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

        $this->assertTrue($mockTarget->methodWasCalled("update", [
            new ArrayTabularDataset([new Field("bong")], [
                [
                    "bong" => "bong"
                ]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

    }


    public function testIfFieldArrayIncludedInTargetDatasourceObjectThisIsUsedInsteadOfSourceFields() {

        $mockSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockSourceInstance->returnValue("returnDataSource", $mockSource);


        $dataSet = new ArrayTabularDataset([new Field("name"), new Field("age"), new Field("shoeSize")], [
            [
                "name" => "Bobby",
                "age" => 23,
                "shoeSize" => 10
            ],
            [
                "name" => "Mary",
                "age" => 25,
                "shoeSize" => 9
            ]
        ]);

        $this->datasourceService->returnValue("getEvaluatedDataSource", $dataSet, [
            "source", null, null, 0, 500
        ]);

        $mockTargetInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockTarget = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $mockTargetInstance->returnValue("returnDataSource", $mockTarget);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockTargetInstance, [
            "target"
        ]);

        $config = new TabularDatasourceImportProcessorConfiguration("source", [
            new TargetDatasource("target", [
                new Field("name", "Title"),
                new Field("shoeSize")
            ])
        ]);

        $this->processor->process($config);

        $this->assertTrue($mockTarget->methodWasCalled("update", [
            new ArrayTabularDataset([new Field("name", "Title"),
                new Field("shoeSize")], [
                [
                    "name" => "Bobby",
                    "age" => 23,
                    "shoeSize" => 10
                ],
                [
                    "name" => "Mary",
                    "age" => 25,
                    "shoeSize" => 9
                ]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));


    }

    public function testIfTargetFieldsIncludedWithFieldMapperConfigTheseAreAppliedToDataValues() {


        $mockSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockSourceInstance->returnValue("returnDataSource", $mockSource);

        $dataSet = new ArrayTabularDataset([new Field("name"), new Field("dob"), new Field("shoeSize")], [
            [
                "name" => "Bobby",
                "dob" => "01/12/1990",
                "shoeSize" => 10
            ],
            [
                "name" => "Mary",
                "dob" => "23/05/1977",
                "shoeSize" => 9
            ]
        ]);

        $this->datasourceService->returnValue("getEvaluatedDataSource", $dataSet, [
            "source", null, null, 0, 500
        ]);

        $mockTargetInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockTarget = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $mockTargetInstance->returnValue("returnDataSource", $mockTarget);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockTargetInstance, [
            "target"
        ]);

        $config = new TabularDatasourceImportProcessorConfiguration("source", [
            new TargetDatasource("target", [
                new Field("name", "Title"),
                new TargetField("dob", "date_of_birth", "date", [
                    "sourceFormat" => "d/m/Y",
                    "targetFormat" => "Y-m-d"
                ]),
                new Field("shoeSize")
            ])
        ]);

        $this->processor->process($config);


        $this->assertTrue($mockTarget->methodWasCalled("update", [
            new ArrayTabularDataset([new Field("name", "Title"),
                new Field("date_of_birth"),
                new Field("shoeSize")], [
                [
                    "name" => "Bobby",
                    "date_of_birth" => "1990-12-01",
                    "shoeSize" => 10
                ],
                [
                    "name" => "Mary",
                    "date_of_birth" => "1977-05-23",
                    "shoeSize" => 9
                ]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));


    }


}