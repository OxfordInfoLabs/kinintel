<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Asynchronous\Asynchronous;
use Kinikit\Core\Asynchronous\AsynchronousClassMethod;
use Kinikit\Core\Asynchronous\Processor\AsynchronousProcessor;
use Kinikit\Core\Asynchronous\Processor\SynchronousProcessor;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Reflection\ClassInspector;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Controllers\Internal\ProcessedDataset;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\ProcessedTabularDataSet;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Join\JoinColumn;
use Kinintel\ValueObjects\Transformation\Join\JoinParameterMapping;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\TestTransformation;
use Masterminds\HTML5\Exception;

include_once "autoloader.php";

class JoinTransformationProcessorTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $dataSourceService;

    /**
     * @var MockObject
     */
    private $dataSetService;


    /**
     * @var SynchronousProcessor
     */
    private $synchronousProcessor;


    /***
     * @var MockObject
     */
    private $asynchronousProcessor;


    /**
     * @var MockObject
     */
    private $authCredentials;

    /**
     * @var MockObject
     */
    private $validator;

    /**
     * @var JoinTransformationProcessor
     */
    private $processor;


    public function setUp(): void {
        $this->authCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);
        $this->dataSourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->dataSetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->synchronousProcessor = MockObjectProvider::instance()->getMockInstance(AsynchronousProcessor::class);
        $this->asynchronousProcessor = MockObjectProvider::instance()->getMockInstance(AsynchronousProcessor::class);
        $this->processor = new JoinTransformationProcessor($this->dataSourceService, $this->dataSetService, $this->synchronousProcessor, $this->asynchronousProcessor);
        $this->validator = MockObjectProvider::instance()->getMockInstance(Validator::class);
    }


    public function testIfJoinTransformationSuppliedToApplyTransformationWithSameCredsDatasourceIsReturnedIntact() {


        $joinDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $joinDatasourceInstance, [
            "testjoindatasource"
        ]);
        $joinDatasourceInstance->returnValue("returnDataSource", $joinDatasource);

        $transformation = new JoinTransformation("testjoindatasource");

        // Programme same creds, i.e. nothing to do.
        $joinDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(
            new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials,
            new DatasourceUpdateConfig(),
            $this->validator,
            Container::instance()->get(TableDDLGenerator::class)
        );


        $transformedDatasource = $this->processor->applyTransformation($transformation, $sqlDatabaseDatasource, []);

        $this->assertEquals($sqlDatabaseDatasource, $transformedDatasource);


    }

    public function testIfJoinTransformationSuppliedToApplyTransformationWithDataSourceWithDifferentCredsNewDefaultDatasourceReturnedAndCreatedForTransformation() {


        $joinDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $joinDatasourceInstance, [
            "testjoindatasource"
        ]);
        $joinDatasourceInstance->returnValue("returnDataSource", $joinDatasource);

        $transformation = new JoinTransformation("testjoindatasource");

        // Programme different creds - should convert
        $differentCreds = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentials::class);
        $joinDatasource->returnValue("getAuthenticationCredentials", $differentCreds);
        $joinDatasource->returnValue("materialise", new ArrayTabularDataset([new Field("name")], []), [[]]);

        $sqlDatabaseDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $sqlDatabaseDatasource->returnValue("materialise", new ArrayTabularDataset([new Field("test")], []));

        $transformedDatasource = $this->processor->applyTransformation($transformation, $sqlDatabaseDatasource, []);

        $this->assertInstanceOf(DefaultDatasource::class, $transformedDatasource);
        $this->assertEquals($sqlDatabaseDatasource, $transformedDatasource->returnSourceDatasource());


        $this->assertInstanceOf(DefaultDatasource::class, $transformation->returnEvaluatedDataSource());
        $this->assertEquals($joinDatasource, $transformation->returnEvaluatedDataSource()->returnSourceDatasource());


    }


    public function testIfJoinTransformationSuppliedToApplyTransformationWithDataSetWithDifferentCredsNewDefaultDatasourceReturnedAndCreatedForTransformation() {


        $joinDataSetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $joinDataSetInstance->returnValue("getDatasourceInstanceKey", "testjoindataset");
        $joinDataSetInstance->returnValue("getTransformationInstances", [
            new TestTransformation(), new TestTransformation()
        ]);
        $this->dataSetService->returnValue("getDataSetInstance", $joinDataSetInstance, [10]);

        $joinDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $this->dataSetService->returnValue("getTransformedDatasourceForDataSetInstance", $joinDatasource,
            [$joinDataSetInstance, [], []]
        );

        $transformation = new JoinTransformation(null, 10);

        // Programme different creds - should convert
        $differentCreds = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentials::class);
        $joinDatasource->returnValue("getAuthenticationCredentials", $differentCreds);
        $joinDatasource->returnValue("materialise", new ArrayTabularDataset([new Field("name")], []), [[]]);


        $sqlDatabaseDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $sqlDatabaseDatasource->returnValue("materialise", new ArrayTabularDataset([new Field("test")], []));

        $transformedDatasource = $this->processor->applyTransformation($transformation, $sqlDatabaseDatasource, []);


        $this->assertInstanceOf(DefaultDatasource::class, $transformation->returnEvaluatedDataSource());
        $this->assertEquals($joinDatasource, $transformation->returnEvaluatedDataSource()->returnSourceDatasource());

        // Check that the new transformed datasource is default with transformation attached.
        $this->assertInstanceOf(DefaultDatasource::class, $transformedDatasource);
        $this->assertEquals($sqlDatabaseDatasource, $transformedDatasource->returnSourceDatasource());
        $this->assertEquals([$transformation], $transformedDatasource->returnTransformations());


    }


    public function testOnApplyTransformationIfJoinColumnsSuppliedSourceDatasetColumnsAreEvaluated() {


        $joinDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $joinDatasourceInstance, [
            "testjoindatasource"
        ]);
        $joinDatasourceInstance->returnValue("returnDataSource", $joinDatasource);

        $transformation = new JoinTransformation("testjoindatasource", null, [], [], [
            new Field("name")
        ]);

        // Programme same creds, i.e. nothing to do.
        $joinDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);

        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $this->authCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);

        $mainDataSource->returnValue("materialise", new ArrayTabularDataset([
            new Field("otherName"), new Field("notes")
        ], []));

        $this->processor->applyTransformation($transformation, $mainDataSource, []);

        $this->assertTrue($mainDataSourceConfig->methodWasCalled("setColumns", [
            [
                new Field("otherName"), new Field("notes")
            ]
        ]));


    }


    public function testTransformationExceptionRaisedIfJoinDatasourceHasDefinedParametersWhichHaveNotBeenFulfilledOnApply() {


        $joinDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $this->dataSourceService->returnValue("getDataSourceInstanceByKey", $joinDatasourceInstance, [
            "testjoindatasource"
        ]);
        $joinDatasourceInstance->returnValue("returnDataSource", $joinDatasource);

        $transformation = new JoinTransformation("testjoindatasource");

        // Programme same creds, i.e. nothing to do.
        $joinDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);

        $joinDatasourceInstance->returnValue("getParameters", [
            new Parameter("term", "Term")
        ]);


        $sqlDatabaseDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);

        try {
            $this->processor->applyTransformation($transformation, $sqlDatabaseDatasource, []);
            $this->fail("Should have thrown here");
        } catch (DatasourceTransformationException $e) {
            $this->assertTrue(true);
        }

    }


    public function testTransformationExceptionRaisedIfJoinDatasetHasDefinedParametersWhichHaveNotBeenFulfilledOnApply() {


        $joinDataSetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $joinDataSetInstance->returnValue("getDatasourceInstanceKey", "testjoindataset");
        $joinDataSetInstance->returnValue("getTransformationInstances", [
            new TestTransformation(), new TestTransformation()
        ]);

        $this->dataSetService->returnValue("getDataSetInstance", $joinDataSetInstance, [10]);

        $joinDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $this->dataSetService->returnValue("getTransformedDatasourceForDataSetInstance", $joinDatasource,
            [$joinDataSetInstance, [], []]
        );

        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            $joinDataSetInstance
        ]);

        $transformation = new JoinTransformation(null, 10);

        // Programme different creds - should convert
        $differentCreds = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentials::class);
        $joinDatasource->returnValue("getAuthenticationCredentials", $differentCreds);

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(
            new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials,
            new DatasourceUpdateConfig(),
            $this->validator,
        );

        try {
            $this->processor->applyTransformation($transformation, $sqlDatabaseDatasource, []);
            $this->fail("Should have thrown here");
        } catch (DatasourceTransformationException $e) {
            $this->assertTrue(true);
        }

    }


    public function testParameterisedDataSourceWithParametersMappedToParametersAreFulfilledByMaterialisingWithMappedParameters() {

        $joinDataSetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $joinDataSetInstance->returnValue("getDatasourceInstanceKey", "testjoindataset");
        $joinDataSetInstance->returnValue("getTransformationInstances", [
            new TestTransformation(), new TestTransformation()
        ]);

        $this->dataSetService->returnValue("getDataSetInstance", $joinDataSetInstance, [10]);

        $joinDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $joinDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);
        $joinDatasource->returnValue("materialise", new ArrayTabularDataset([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "John",
                "column2" => "Brown"
            ],
            [
                "column1" => "Joe",
                "column2" => "Bloggs"
            ]
        ]), [[
            "term" => "Bingo"
        ]]);

        $this->dataSetService->returnValue("getTransformedDatasourceForDataSetInstance", $joinDatasource,
            [$joinDataSetInstance, ["term" => "Bingo"], []]
        );


        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            $joinDataSetInstance
        ]);

        $transformation = new JoinTransformation(null, 10, [
            new JoinParameterMapping("term", "passedTerm")
        ]);

        $mainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);
        $mainDatasource->returnValue("materialise", new ArrayTabularDataset([new Field("test")], []));


        $this->processor->applyTransformation($transformation, $mainDatasource, [
            "passedTerm" => "Bingo"
        ]);

        $evaluatedDatasource = $transformation->returnEvaluatedDataSource();
        $this->assertInstanceOf(DefaultDatasource::class, $evaluatedDatasource);
        $this->assertEquals([[
            "column1" => "John",
            "column2" => "Brown"
        ],
            [
                "column1" => "Joe",
                "column2" => "Bloggs"
            ]], $evaluatedDatasource->materialise()->getAllData());

    }


    public function testParameterisedDataSourceWithParametersMappedToColumnsAreFulfilledByMaterialisingMultipleTimesForEachColumn() {

        $joinDataSetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $joinDataSetInstance->returnValue("getDatasourceInstanceKey", "testjoindataset");
        $joinDataSetInstance->returnValue("getTransformationInstances", [
            new TestTransformation(), new TestTransformation()
        ]);

        $this->dataSetService->returnValue("getDataSetInstance", $joinDataSetInstance, [10]);


        $inputAsync1 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bingo"]
        ]);

        $inputAsync2 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bongo"]
        ]);

        $inputAsync3 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bango"]
        ]);


        $outputAsync1 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync1->returnValue("getStatus", Asynchronous::STATUS_COMPLETED);
        $outputAsync1->returnValue("getReturnValue", new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "John",
                "column2" => "Brown"
            ],
            [
                "column1" => "Joe",
                "column2" => "Bloggs"
            ]
        ]));

        $outputAsync2 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync2->returnValue("getStatus", Asynchronous::STATUS_COMPLETED);
        $outputAsync2->returnValue("getReturnValue", new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "Jane",
                "column2" => "White"
            ],
            [
                "column1" => "Andrew",
                "column2" => "Smythe"
            ]
        ]));


        $outputAsync3 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync3->returnValue("getStatus", Asynchronous::STATUS_COMPLETED);
        $outputAsync3->returnValue("getReturnValue", new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "Peter",
                "column2" => "Piper"
            ],
            [
                "column1" => "Humpty",
                "column2" => "Dumpty"
            ]
        ]));

        $this->synchronousProcessor->returnValue("executeAndWait", [
            $outputAsync1, $outputAsync2, $outputAsync3
        ], [[$inputAsync1, $inputAsync2, $inputAsync3]]);

        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            $joinDataSetInstance
        ]);

        $transformation = new JoinTransformation(null, 10, [
            new JoinParameterMapping("term", null, "expression")
        ]);

        $mainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);

        $mainDatasource->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table"));

        $mainDatasource->returnValue("materialise", new ArrayTabularDataset([
            new Field("title", "Title"),
            new Field("expression", "Expression")
        ], [
            [
                "title" => "Test 1",
                "expression" => "Bingo"
            ],
            [
                "title" => "Test 2",
                "expression" => "Bongo"
            ], [
                "title" => "Test 3",
                "expression" => "Bango"
            ]
        ]), [[]]);


        $this->processor->applyTransformation($transformation, $mainDatasource, []);

        $evaluatedDatasource = $transformation->returnEvaluatedDataSource();
        $this->assertInstanceOf(DefaultDatasource::class, $evaluatedDatasource);
        $this->assertEquals([[
            "alias_1" => "Bingo",
            "column1" => "John",
            "column2" => "Brown"
        ],
            [
                "alias_1" => "Bingo",
                "column1" => "Joe",
                "column2" => "Bloggs"
            ],
            [
                "alias_1" => "Bongo",
                "column1" => "Jane",
                "column2" => "White"
            ],
            [
                "alias_1" => "Bongo",
                "column1" => "Andrew",
                "column2" => "Smythe"
            ],
            [
                "alias_1" => "Bango",
                "column1" => "Peter",
                "column2" => "Piper"
            ],
            [
                "alias_1" => "Bango",
                "column1" => "Humpty",
                "column2" => "Dumpty"
            ]
        ], $evaluatedDatasource->materialise()->getAllData());

    }


    public function testWhereJoinConcurrencyIsDefinedParameterisedDataSourceWithParametersMappedToColumnsAreFulfilledUsingLoopbackAsynchronousProcessor() {

        // Set concurrency to 2
        Configuration::instance()->addParameter("sqldatabase.datasource.join.default.concurrency", 2);


        $joinDataSetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $joinDataSetInstance->returnValue("getDatasourceInstanceKey", "testjoindataset");
        $joinDataSetInstance->returnValue("getTransformationInstances", [
            new TestTransformation(), new TestTransformation()
        ]);


        $this->dataSetService->returnValue("getDataSetInstance", $joinDataSetInstance, [10]);


        $inputAsynchronous1 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bingo"]
        ]);


        $inputAsynchronous2 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bongo"]
        ]);

        $inputAsynchronous3 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bango"]
        ]);

        $inputAsync1 = [$inputAsynchronous1, $inputAsynchronous2];
        $inputAsync2 = [$inputAsynchronous3];


        $classInspector = new ClassInspector(AsynchronousClassMethod::class);


        $outputAsynchronous1 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bingo"]
        ]);
        $classInspector->setPropertyData($outputAsynchronous1, new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "John",
                "column2" => "Brown"
            ],
            [
                "column1" => "Joe",
                "column2" => "Bloggs"
            ]
        ]), "returnValue", false);
        $outputAsynchronous1->setStatus(Asynchronous::STATUS_COMPLETED);

        $outputAsynchronous2 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bongo"]
        ]);
        $outputAsynchronous2->setStatus(Asynchronous::STATUS_COMPLETED);
        $classInspector->setPropertyData($outputAsynchronous2, new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "Jane",
                "column2" => "White"
            ],
            [
                "column1" => "Andrew",
                "column2" => "Smythe"
            ]
        ]), "returnValue", false);


        $outputAsynchronous3 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bango"]
        ]);
        $classInspector->setPropertyData($outputAsynchronous3, new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "Peter",
                "column2" => "Piper"
            ],
            [
                "column1" => "Humpty",
                "column2" => "Dumpty"
            ]
        ]), "returnValue", false);
        $outputAsynchronous3->setStatus(Asynchronous::STATUS_COMPLETED);

        $outputAsync1 = [$outputAsynchronous1, $outputAsynchronous2];
        $outputAsync2 = [$outputAsynchronous3];


        // Programme asynchronous processor
        $this->asynchronousProcessor->returnValue("executeAndWait", $outputAsync1, [$inputAsync1]);
        $this->asynchronousProcessor->returnValue("executeAndWait", $outputAsync2, [$inputAsync2]);


        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            $joinDataSetInstance
        ]);


        $transformation = new JoinTransformation(null, 10, [
            new JoinParameterMapping("term", null, "expression")
        ]);

        $mainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);

        $mainDatasource->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table"));

        $mainDatasource->returnValue("materialise", new ArrayTabularDataset([
            new Field("title", "Title"),
            new Field("expression", "Expression")
        ], [
            [
                "title" => "Test 1",
                "expression" => "Bingo"
            ],
            [
                "title" => "Test 2",
                "expression" => "Bongo"
            ], [
                "title" => "Test 3",
                "expression" => "Bango"
            ]
        ]), [[]]);

        $this->processor = new JoinTransformationProcessor($this->dataSourceService,$this->dataSetService,MockObjectProvider::instance()->getMockInstance(SynchronousProcessor::class),$this->asynchronousProcessor);

        $this->processor->applyTransformation($transformation, $mainDatasource, []);

        $evaluatedDatasource = $transformation->returnEvaluatedDataSource();
        $this->assertInstanceOf(DefaultDatasource::class, $evaluatedDatasource);
        $this->assertEquals([[
            "alias_1" => "Bingo",
            "column1" => "John",
            "column2" => "Brown"
        ],
            [
                "alias_1" => "Bingo",
                "column1" => "Joe",
                "column2" => "Bloggs"
            ],
            [
                "alias_1" => "Bongo",
                "column1" => "Jane",
                "column2" => "White"
            ],
            [
                "alias_1" => "Bongo",
                "column1" => "Andrew",
                "column2" => "Smythe"
            ],
            [
                "alias_1" => "Bango",
                "column1" => "Peter",
                "column2" => "Piper"
            ],
            [
                "alias_1" => "Bango",
                "column1" => "Humpty",
                "column2" => "Dumpty"
            ]
        ], $evaluatedDatasource->materialise()->getAllData());

    }


    public function testIfExceptionRaisedForParameterisedDataSourceWithParametersMappedToColumnsExceptionIsIgnoredAndBlankDataReturnedForRow() {


        // Set concurrency to 2
        Configuration::instance()->removeParameter("sqldatabase.datasource.join.default.concurrency");


        $joinDataSetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $joinDataSetInstance->returnValue("getDatasourceInstanceKey", "testjoindataset");
        $joinDataSetInstance->returnValue("getTransformationInstances", [
            new TestTransformation(), new TestTransformation()
        ]);

        $this->dataSetService->returnValue("getDataSetInstance", $joinDataSetInstance, [10]);

        $inputAsync1 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bingo"]
        ]);

        $inputAsync2 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bongo"]
        ]);

        $inputAsync3 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bango"]
        ]);


        $outputAsync1 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync1->returnValue("getStatus", Asynchronous::STATUS_FAILED);
        $outputAsync1->returnValue("getExceptionData", new Exception("Bad request"));

        $outputAsync2 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync2->returnValue("getStatus", Asynchronous::STATUS_FAILED);
        $outputAsync1->returnValue("getExceptionData", new Exception("Bad request"));


        $outputAsync3 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync3->returnValue("getStatus", Asynchronous::STATUS_COMPLETED);
        $outputAsync3->returnValue("getReturnValue", new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "Peter",
                "column2" => "Piper"
            ],
            [
                "column1" => "Humpty",
                "column2" => "Dumpty"
            ]
        ]));

        $this->synchronousProcessor->returnValue("executeAndWait", [
            $outputAsync1, $outputAsync2, $outputAsync3
        ], [[$inputAsync1, $inputAsync2, $inputAsync3]]);

        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            $joinDataSetInstance
        ]);

        $transformation = new JoinTransformation(null, 10, [
            new JoinParameterMapping("term", null, "expression")
        ]);

        $mainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);

        $mainDatasource->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table"));

        $mainDatasource->returnValue("materialise", new ArrayTabularDataset([
            new Field("title", "Title"),
            new Field("expression", "Expression")
        ], [
            [
                "title" => "Test 1",
                "expression" => "Bingo"
            ],
            [
                "title" => "Test 2",
                "expression" => "Bongo"
            ], [
                "title" => "Test 3",
                "expression" => "Bango"
            ]
        ]), [[]]);


        $this->processor->applyTransformation($transformation, $mainDatasource, []);

        $evaluatedDatasource = $transformation->returnEvaluatedDataSource();
        $this->assertInstanceOf(DefaultDatasource::class, $evaluatedDatasource);
        $this->assertEquals([
            [
                "alias_1" => "Bingo",
                "column1" => null,
                "column2" => null
            ],
            [
                "alias_1" => "Bongo",
                "column1" => null,
                "column2" => null
            ],
            [
                "alias_1" => "Bango",
                "column1" => "Peter",
                "column2" => "Piper"
            ],
            [
                "alias_1" => "Bango",
                "column1" => "Humpty",
                "column2" => "Dumpty"
            ]
        ], $evaluatedDatasource->materialise()->getAllData());

    }


    public function testIfPagingTransformationPassedThroughToApplyMethodItIsAppliedFirstToParentSetWhenParameterBasedJoin() {
        $joinDataSetInstance = MockObjectProvider::instance()->getMockInstance(DatasetInstance::class);
        $joinDataSetInstance->returnValue("getDatasourceInstanceKey", "testjoindataset");
        $joinDataSetInstance->returnValue("getTransformationInstances", [
            new TestTransformation(), new TestTransformation()
        ]);

        $this->dataSetService->returnValue("getDataSetInstance", $joinDataSetInstance, [10]);

        $inputAsync1 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bingo"]
        ]);

        $inputAsync2 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bongo"]
        ]);

        $inputAsync3 = new AsynchronousClassMethod(ProcessedDataset::class, "getProcessedTabularDatasetForDatasetInstance", [
            "dataSetInstance" => $joinDataSetInstance, "parameterValues" => ["term" => "Bango"]
        ]);


        $outputAsync1 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync1->returnValue("getStatus", Asynchronous::STATUS_COMPLETED);
        $outputAsync1->returnValue("getReturnValue", new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "John",
                "column2" => "Brown"
            ],
            [
                "column1" => "Joe",
                "column2" => "Bloggs"
            ]
        ]));

        $outputAsync2 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync2->returnValue("getStatus", Asynchronous::STATUS_COMPLETED);
        $outputAsync2->returnValue("getReturnValue", new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "Jane",
                "column2" => "White"
            ],
            [
                "column1" => "Andrew",
                "column2" => "Smythe"
            ]
        ]));


        $outputAsync3 = MockObjectProvider::instance()->getMockInstance(Asynchronous::class);
        $outputAsync3->returnValue("getStatus", Asynchronous::STATUS_COMPLETED);
        $outputAsync3->returnValue("getReturnValue", new ProcessedTabularDataSet([
            new Field("column1"), new Field("column2")
        ], [
            [
                "column1" => "Peter",
                "column2" => "Piper"
            ],
            [
                "column1" => "Humpty",
                "column2" => "Dumpty"
            ]
        ]));

        $this->synchronousProcessor->returnValue("executeAndWait", [
            $outputAsync1, $outputAsync2, $outputAsync3
        ], [[$inputAsync1, $inputAsync2, $inputAsync3]]);

        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            $joinDataSetInstance
        ]);

        $transformation = new JoinTransformation(null, 10, [
            new JoinParameterMapping("term", null, "expression")
        ]);

        $mainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);

        $mainDatasource->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table"));

        // Expect a paged main datasource from paging transformation
        $pagedMainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("applyTransformation", $pagedMainDatasource, [
            new PagingTransformation(5, 0), []
        ]);

        $pagedMainDatasource->returnValue("getConfig", new SQLDatabaseDatasourceConfig("table"));


        $pagedMainDatasource->returnValue("materialise", new ArrayTabularDataset([
            new Field("title", "Title"),
            new Field("expression", "Expression")
        ], [
            [
                "title" => "Test 1",
                "expression" => "Bingo"
            ],
            [
                "title" => "Test 2",
                "expression" => "Bongo"
            ], [
                "title" => "Test 3",
                "expression" => "Bango"
            ]
        ]), [[]]);


        $this->processor->applyTransformation($transformation, $mainDatasource, [], new PagingTransformation(5, 0));

        $evaluatedDatasource = $transformation->returnEvaluatedDataSource();
        $this->assertInstanceOf(DefaultDatasource::class, $evaluatedDatasource);
        $this->assertEquals([[
            "alias_1" => "Bingo",
            "column1" => "John",
            "column2" => "Brown"
        ],
            [
                "alias_1" => "Bingo",
                "column1" => "Joe",
                "column2" => "Bloggs"
            ],
            [
                "alias_1" => "Bongo",
                "column1" => "Jane",
                "column2" => "White"
            ],
            [
                "alias_1" => "Bongo",
                "column1" => "Andrew",
                "column2" => "Smythe"
            ],
            [
                "alias_1" => "Bango",
                "column1" => "Peter",
                "column2" => "Piper"
            ],
            [
                "alias_1" => "Bango",
                "column1" => "Humpty",
                "column2" => "Dumpty"
            ]
        ], $evaluatedDatasource->materialise()->getAllData());


    }


    public function testCanJoinToDatasourceUsingSameAuthenticationCredsAndSQLJoinQueryIsCreatedForColumnAndValueBasedJoins() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);

        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", new SQLQuery("*", "join_table"), [
            []
        ]);
        $joinDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try a simple column based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("[[name]]", "[[otherName]]", Filter::FILTER_TYPE_EQUALS)]));

        $joinTransformation->setEvaluatedDataSource($joinDatasource);

        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.*,T2.* FROM (SELECT * FROM test_table) T1 LEFT JOIN (SELECT * FROM join_table) T2 ON T1.\"name\" = T2.\"otherName\") S1"),
            $sqlQuery);


        // Try a simple value based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("[[name]]", "bobby", Filter::FILTER_TYPE_EQUALS)]));

        $joinTransformation->setEvaluatedDataSource($joinDatasource);


        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T3.*,T4.* FROM (SELECT * FROM test_table) T3 LEFT JOIN (SELECT * FROM join_table) T4 ON T3.\"name\" = ?) S2", [
            "bobby"
        ]),
            $sqlQuery);


    }


    public function testIfStrictJoinSetAnInnerJoinClauseIsWrittenInsteadOfLeftJoin() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);

        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", new SQLQuery("*", "join_table"), [
            []
        ]);
        $joinDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try a simple column based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("[[name]]", "[[otherName]]", Filter::FILTER_TYPE_EQUALS)]), [], true);

        $joinTransformation->setEvaluatedDataSource($joinDatasource);

        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.*,T2.* FROM (SELECT * FROM test_table) T1 INNER JOIN (SELECT * FROM join_table) T2 ON T1.\"name\" = T2.\"otherName\") S1"),
            $sqlQuery);

    }


    public function testExistingQueryParametersAreMergedIntoParametersForDatasourceJoinQuery() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);

        $joinQuery = new SQLQuery("*", "join_table");
        $joinQuery->setWhereClause("category = ? AND published = ?", ["swimming", 1]);

        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", $joinQuery, [
            []
        ]);
        $joinDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try a simple column based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("bob", "[[name]]", Filter::FILTER_TYPE_LIKE)]));

        $joinTransformation->setEvaluatedDataSource($joinDatasource);

        $query = new SQLQuery("*", "test_table");
        $query->setWhereClause("archived = ?", [0]);


        $sqlQuery = $this->processor->updateQuery($joinTransformation, $query, [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.*,T2.* FROM (SELECT * FROM test_table WHERE archived = ?) T1 LEFT JOIN (SELECT * FROM join_table WHERE category = ? AND published = ?) T2 ON ? LIKE T2.\"name\") S1",
            [
                0, "swimming", 1, "bob"
            ]),
            $sqlQuery);


    }


    public function testIfJoinColumnsSuppliedToAJoinTransformationTheseAreSelectedExplicitlyFromJoinedTable() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);


        // Ensure joined datasource returns this set of credentials
        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);

        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", new SQLQuery("*", "join_table"), [
            []
        ]);
        $joinDatasource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("otherName"), new Field("notes")]);
        $mainDataSource->returnValue("returnDatabaseConnection", new SQLite3DatabaseConnection());


        // Try simple non aliased columns
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("[[name]]", "[[otherName]]", Filter::FILTER_TYPE_EQUALS)]), [
                new Field("name"), new Field("category"), new Field("status")
            ]);


        $joinTransformation->setEvaluatedDataSource($joinDatasource);

        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.*,T2.name,T2.category,T2.status FROM (SELECT * FROM test_table) T1 LEFT JOIN (SELECT * FROM join_table) T2 ON T1.\"name\" = T2.\"otherName\") S1"),
            $sqlQuery);


        // Check the columns were added as expected
        $this->assertTrue($mainDataSourceConfig->methodWasCalled("setColumns", [[
            new Field("otherName"), new Field("notes"),
            new Field("name", "Name"),
            new Field("category", "Category"), new Field("status", "Status")
        ]]));

    }

}