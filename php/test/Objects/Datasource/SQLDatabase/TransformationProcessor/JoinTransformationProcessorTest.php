<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinikit\MVC\Request\MockPHPInputStream;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
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
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Join\JoinColumn;
use Kinintel\ValueObjects\Transformation\Join\JoinParameterMapping;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TestTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

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
        $this->processor = new JoinTransformationProcessor($this->dataSourceService, $this->dataSetService);
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


        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials, new DatasourceUpdateConfig(), $this->validator, $this->dataSourceService);


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
        $this->dataSourceService->returnValue("getTransformedDataSource", $joinDatasource, [
            "testjoindataset", [
                new TestTransformation(), new TestTransformation()
            ], []
        ]);

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
        $this->dataSourceService->returnValue("getTransformedDataSource", $joinDatasource, [
            "testjoindataset", [
                new TestTransformation(), new TestTransformation()
            ], []
        ]);

        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            10
        ]);

        $transformation = new JoinTransformation(null, 10);

        // Programme different creds - should convert
        $differentCreds = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentials::class);
        $joinDatasource->returnValue("getAuthenticationCredentials", $differentCreds);

        $sqlDatabaseDatasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "test_data", "", true),
            $this->authCredentials, new DatasourceUpdateConfig(), $this->validator, $this->dataSourceService, $this->dataSetService);

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

        $this->dataSourceService->returnValue("getTransformedDataSource", $joinDatasource, [
            "testjoindataset", [
                new TestTransformation(), new TestTransformation()
            ], ["term" => "Bingo"]
        ]);

        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            10
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

        $joinDatasource1 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $joinDatasource1->returnValue("getAuthenticationCredentials", $this->authCredentials);
        $joinDatasource1->returnValue("materialise", new ArrayTabularDataset([
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

        $this->dataSourceService->returnValue("getTransformedDataSource", $joinDatasource1, [
            "testjoindataset", [
                new TestTransformation(), new TestTransformation()
            ], ["term" => "Bingo"]
        ]);


        $joinDatasource2 = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $joinDatasource2->returnValue("getAuthenticationCredentials", $this->authCredentials);
        $joinDatasource2->returnValue("materialise", new ArrayTabularDataset([
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
        ]), [[
            "term" => "Bongo"
        ]]);

        $this->dataSourceService->returnValue("getTransformedDataSource", $joinDatasource2, [
            "testjoindataset", [
                new TestTransformation(), new TestTransformation()
            ], ["term" => "Bongo"]
        ]);


        $joinDatasource3= MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $joinDatasource3->returnValue("getAuthenticationCredentials", $this->authCredentials);
        $joinDatasource3->returnValue("materialise", new ArrayTabularDataset([
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
        ]), [[
            "term" => "Bango"
        ]]);


        $this->dataSourceService->returnValue("getTransformedDataSource", $joinDatasource3, [
            "testjoindataset", [
                new TestTransformation(), new TestTransformation()
            ], ["term" => "Bango"]
        ]);

        $this->dataSetService->returnValue("getEvaluatedParameters", [
            new Parameter("term", "Term")
        ], [
            10
        ]);

        $transformation = new JoinTransformation(null, 10, [
            new JoinParameterMapping("term", null, "expression")
        ]);

        $mainDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDatasource->returnValue("getAuthenticationCredentials", $this->authCredentials);

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


    public function testCanJoinToDatasourceUsingSameAuthenticationCredsAndSQLJoinQueryIsCreatedForColumnAndValueBasedJoins() {

        // Create set of authentication credentials
        $authenticationCredentials = MockObjectProvider::instance()->getMockInstance(SQLiteAuthenticationCredentials::class);

        $joinDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $joinDatasource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $joinDatasource->returnValue("buildQuery", new SQLQuery("*", "join_table"), [
            []
        ]);

        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);


        // Try a simple column based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", "[[otherName]]", Filter::FILTER_TYPE_EQUALS)]));

        $joinTransformation->setEvaluatedDataSource($joinDatasource);

        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.*,T2.* FROM (SELECT * FROM test_table) T1 LEFT JOIN (SELECT * FROM join_table) T2 ON T2.name = T1.otherName) S1"),
            $sqlQuery);


        // Try a simple value based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", "bobby", Filter::FILTER_TYPE_EQUALS)]));

        $joinTransformation->setEvaluatedDataSource($joinDatasource);


        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T3.*,T4.* FROM (SELECT * FROM test_table) T3 LEFT JOIN (SELECT * FROM join_table) T4 ON T4.name = ?) S2", [
            "bobby"
        ]),
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


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);


        // Try a simple column based join
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", "*bob*", Filter::FILTER_TYPE_LIKE)]));

        $joinTransformation->setEvaluatedDataSource($joinDatasource);

        $query = new SQLQuery("*", "test_table");
        $query->setWhereClause("archived = ?", [0]);


        $sqlQuery = $this->processor->updateQuery($joinTransformation, $query, [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.*,T2.* FROM (SELECT * FROM test_table WHERE archived = ?) T1 LEFT JOIN (SELECT * FROM join_table WHERE category = ? AND published = ?) T2 ON T2.name LIKE ?) S1",
            [
                0, "swimming", 1, "%bob%"
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


        $mainDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mainDataSourceConfig = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);

        $mainDataSource->returnValue("getAuthenticationCredentials", $authenticationCredentials);
        $mainDataSource->returnValue("getConfig", $mainDataSourceConfig);
        $mainDataSourceConfig->returnValue("getColumns", [new Field("otherName"), new Field("notes")]);


        // Try simple non aliased columns
        $joinTransformation = new JoinTransformation("testsource", null, [],
            new FilterJunction([
                new Filter("name", "[[otherName]]", Filter::FILTER_TYPE_EQUALS)]), [
                new Field("name"), new Field("category"), new Field("status")
            ]);


        $joinTransformation->setEvaluatedDataSource($joinDatasource);

        $sqlQuery = $this->processor->updateQuery($joinTransformation, new SQLQuery("*", "test_table"), [],
            $mainDataSource);


        $this->assertEquals(new SQLQuery("*", "(SELECT T1.*,T2.name alias_1,T2.category alias_2,T2.status alias_3 FROM (SELECT * FROM test_table) T1 LEFT JOIN (SELECT * FROM join_table) T2 ON T2.name = T1.otherName) S1"),
            $sqlQuery);


        // Check the columns were added as expected
        $this->assertTrue($mainDataSourceConfig->methodWasCalled("setColumns", [[
            new Field("otherName"), new Field("notes"),
            new Field("alias_1", "Name"),
            new Field("alias_2", "Category"), new Field("alias_3", "Status")
        ]]));

    }


}