<?php


namespace Kinintel\ValueObjects\Datasource;

use Google\Service\Analytics\Resource\Data;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\ValueFunction\ValueFunctionEvaluator;
use Kinikit\Core\Testing\ConcreteClassGenerator;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseUpdatableDatasource;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use PHPUnit\Framework\MockObject\MockObject;
use function Kinikit\Core\Util\println;

include_once "autoloader.php";

/**
 * Test cases for the base updatable datasource - especially the saving of mapped field data
 *
 * Class BaseUpdatableDatasourceTest
 * @package Kinintel\ValueObjects\Datasource
 */
class BaseUpdatableDatasourceTest extends TestBase {

    /**
     * @var BaseUpdatableDatasource
     */
    private $datasource;

    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var MockObject
     */
    private $notesDatasource;

    private ValueFunctionEvaluator $valueFunctionEvaluator;


    public function setUp(): void {
        $this->datasource = ConcreteClassGenerator::instance()->generateInstance(BaseUpdatableDatasource::class);

        $this->datasourceService = MockObjectProvider::mock(DatasourceService::class);
        $datasourceInstance = MockObjectProvider::mock(DatasourceInstance::class);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            $datasourceInstance);
        $this->notesDatasource = MockObjectProvider::mock(BaseUpdatableDatasource::class);
        $datasourceInstance->returnValue("returnDataSource", $this->notesDatasource);
        $this->valueFunctionEvaluator = Container::instance()->get(ValueFunctionEvaluator::class);
        $this->datasource->setDatasourceService($this->datasourceService);
        $this->datasource->setValueFunctionEvaluator($this->valueFunctionEvaluator);

    }

    public function testCanUpdateSimpleMappedFieldDataIfDefinedInConfig() {

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes")
        ]);

        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $dataSet = $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"), new Field("notes")], [
            [
                "id" => 1,
                "notes" => [
                    [
                        "id" => 1,
                        "title" => "Item 1"
                    ],
                    [
                        "id" => 2,
                        "title" => "Item 2"
                    ]
                ]
            ],
            [
                "id" => 2,
                "notes" => [
                    [
                        "id" => 3,
                        "title" => "Item 3"
                    ],
                    [
                        "id" => 4,
                        "title" => "Item 4"
                    ]
                ]
            ]

        ]));

        // Check columns and data pruned
        $this->assertEquals([new Field("id")], $dataSet->getColumns());
        $this->assertEquals([["id" => 1], ["id" => 2]], $dataSet->getAllData());

        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("id"),
                    new Field("title")
                ],
                    [
                        [
                            "id" => 1,
                            "title" => "Item 1"
                        ],
                        [
                            "id" => 2,
                            "title" => "Item 2"
                        ],
                        [
                            "id" => 3,
                            "title" => "Item 3"
                        ],
                        [
                            "id" => 4,
                            "title" => "Item 4"
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_ADD
            ]

        ));

    }


    public function testIfParentFiltersSuppliedInConfigTheItemsAreFilteredUsingAdditiveFilteringOnSuppliedValues(){

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [
                "id" => 2
            ])
        ]);

        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $dataSet = $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"),
            new Field("title"),
            new Field("notes")], [
            [
                "id" => 1,
                "title" => "Test",
                "notes" => [
                    [
                        "id" => 1,
                        "title" => "Item 1"
                    ],
                    [
                        "id" => 2,
                        "title" => "Item 2"
                    ]
                ]
            ],
            [
                "id" => 2,
                "title" => "Pick",
                "notes" => [
                    [
                        "id" => 3,
                        "title" => "Item 3"
                    ],
                    [
                        "id" => 4,
                        "title" => "Item 4"
                    ]
                ]
            ]

        ]));

        // Check columns and data pruned
        $this->assertEquals([new Field("id"), new Field("title")], $dataSet->getColumns());
        $this->assertEquals([["id" => 1, "title" => "Test"], ["id" => 2, "title" => "Pick"]], $dataSet->getAllData());

        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("id"),
                    new Field("title")
                ],
                    [
                        [
                            "id" => 3,
                            "title" => "Item 3"
                        ],
                        [
                            "id" => 4,
                            "title" => "Item 4"
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_ADD
            ]

        ));







    }


    public function testIfArrayOfValuesPassedTheyAreMappedToObjectUsingTargetFieldNameProperty() {

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [], [], [], null, "noteText")
        ]);

        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $dataSet = $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"), new Field("notes")], [
            [
                "id" => 1,
                "notes" => [
                    "I am a happy person",
                    "I love singing"
                ]
            ],
            [
                "id" => 2,
                "notes" => [
                    "I am a sad person",
                    "I don't love singing"
                ]
            ]

        ]));

        // Check columns and data pruned
        $this->assertEquals([new Field("id")], $dataSet->getColumns());
        $this->assertEquals([["id" => 1], ["id" => 2]], $dataSet->getAllData());

        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("noteText")
                ],
                    [
                        [
                            "noteText" => "I am a happy person"
                        ],
                        [
                            "noteText" => "I love singing"
                        ],
                        [
                            "noteText" => "I am a sad person"
                        ],
                        [
                            "noteText" => "I don't love singing"
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_ADD
            ]

        ));

    }


    public function testIfSingleValuePassedWithRetainTargetFieldSetTrueTheyAreMappedToObjectUsingTargetFieldNamePropertyAndRetainedInParent() {

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [], [], [], null, "noteText", true)
        ]);

        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $dataSet = $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"), new Field("notes")], [
            [
                "id" => 1,
                "notes" => "I am a happy person"
            ],
            [
                "id" => 2,
                "notes" => "I am a sad person"
            ]

        ]));

        // Check columns and data not pruned
        $this->assertEquals([new Field("id"), new Field("notes")], $dataSet->getColumns());
        $this->assertEquals([["id" => 1, "notes" => "I am a happy person"], ["id" => 2, "notes" => "I am a sad person"]], $dataSet->getAllData());

        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("noteText")
                ],
                    [
                        [
                            "noteText" => "I am a happy person"
                        ],
                        [
                            "noteText" => "I am a sad person"
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_ADD
            ]

        ));

    }


    public function testParentFieldDataMergedInToMappedDataIfDefinedInConfig() {

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [],
                [
                    "id" => "parentId",
                    "description" => "description"
                ])
        ]);

        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $dataSet = $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"), new Field("description"), new Field("notes")], [
            [
                "id" => 55,
                "description" => "Hey Bob",
                "notes" => [
                    [
                        "id" => 1,
                        "title" => "Item 1"
                    ],
                    [
                        "id" => 2,
                        "title" => "Item 2"
                    ]
                ]
            ],
            [
                "id" => 77,
                "description" => "Hey Mary",
                "notes" => [
                    [
                        "id" => 3,
                        "title" => "Item 3"
                    ],
                    [
                        "id" => 4,
                        "title" => "Item 4"
                    ]
                ]
            ]

        ]), BaseUpdatableDatasource::UPDATE_MODE_UPDATE);


        // Check data pruned
        $this->assertEquals([new Field("id"), new Field("description")], $dataSet->getColumns());

        $this->assertEquals([["id" => 55,
            "description" => "Hey Bob"], ["id" => 77,
            "description" => "Hey Mary"]], $dataSet->getAllData());


        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("id"),
                    new Field("title"),
                    new Field("parentId"),
                    new Field("description"),

                ],
                    [
                        [
                            "id" => 1,
                            "title" => "Item 1",
                            "parentId" => 55,
                            "description" => "Hey Bob"
                        ],
                        [
                            "id" => 2,
                            "title" => "Item 2",
                            "parentId" => 55,
                            "description" => "Hey Bob"
                        ],
                        [
                            "id" => 3,
                            "title" => "Item 3",
                            "parentId" => 77,
                            "description" => "Hey Mary"
                        ],
                        [
                            "id" => 4,
                            "title" => "Item 4",
                            "parentId" => 77,
                            "description" => "Hey Mary"
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_UPDATE
            ]

        ));


    }


    public function testParentFieldMappingsCanBeValueExpressionsIfEnclosedInSquareBrackets() {

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [],
                [
                    "[[id | add 20]]" => "parentId",
                    "[[description | lowercase]]" => "description"
                ])
        ]);

        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $dataSet = $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"), new Field("description"), new Field("notes")], [
            [
                "id" => 55,
                "description" => "Hey Bob",
                "notes" => [
                    [
                        "id" => 1,
                        "title" => "Item 1"
                    ],
                    [
                        "id" => 2,
                        "title" => "Item 2"
                    ]
                ]
            ],
            [
                "id" => 77,
                "description" => "Hey Mary",
                "notes" => [
                    [
                        "id" => 3,
                        "title" => "Item 3"
                    ],
                    [
                        "id" => 4,
                        "title" => "Item 4"
                    ]
                ]
            ]

        ]), BaseUpdatableDatasource::UPDATE_MODE_UPDATE);


        // Check data pruned
        $this->assertEquals([new Field("id"), new Field("description")], $dataSet->getColumns());

        $this->assertEquals([["id" => 55,
            "description" => "Hey Bob"], ["id" => 77,
            "description" => "Hey Mary"]], $dataSet->getAllData());


        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("id"),
                    new Field("title"),
                    new Field("parentId"),
                    new Field("description"),

                ],
                    [
                        [
                            "id" => 1,
                            "title" => "Item 1",
                            "parentId" => 75,
                            "description" => "hey bob"
                        ],
                        [
                            "id" => 2,
                            "title" => "Item 2",
                            "parentId" => 75,
                            "description" => "hey bob"
                        ],
                        [
                            "id" => 3,
                            "title" => "Item 3",
                            "parentId" => 97,
                            "description" => "hey mary"
                        ],
                        [
                            "id" => 4,
                            "title" => "Item 4",
                            "parentId" => 97,
                            "description" => "hey mary"
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_UPDATE
            ]

        ));


    }


    public function testIfConstantFieldValuesSuppliedAsPartOfConfigTheseAreMergedIntoMappedData() {

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [],
                [
                    "id" => "parentId",
                    "description" => "description"
                ], [
                    "category" => "STAFF",
                    "public" => 1
                ])
        ]);

        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $dataSet = $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"), new Field("description"), new Field("notes")], [
            [
                "id" => 55,
                "description" => "Hey Bob",
                "notes" => [
                    [
                        "id" => 1,
                        "title" => "Item 1"
                    ],
                    [
                        "id" => 2,
                        "title" => "Item 2"
                    ]
                ]
            ],
            [
                "id" => 77,
                "description" => "Hey Mary",
                "notes" => [
                    [
                        "id" => 3,
                        "title" => "Item 3"
                    ],
                    [
                        "id" => 4,
                        "title" => "Item 4"
                    ]
                ]
            ]

        ]), BaseUpdatableDatasource::UPDATE_MODE_UPDATE);


        // Check data pruned
        $this->assertEquals([new Field("id"), new Field("description")], $dataSet->getColumns());

        $this->assertEquals([["id" => 55,
            "description" => "Hey Bob"], ["id" => 77,
            "description" => "Hey Mary"]], $dataSet->getAllData());


        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("id"),
                    new Field("title"),
                    new Field("parentId"),
                    new Field("description"),
                    new Field("category"),
                    new Field("public")

                ],
                    [
                        [
                            "id" => 1,
                            "title" => "Item 1",
                            "parentId" => 55,
                            "description" => "Hey Bob",
                            "category" => "STAFF",
                            "public" => 1
                        ],
                        [
                            "id" => 2,
                            "title" => "Item 2",
                            "parentId" => 55,
                            "description" => "Hey Bob",
                            "category" => "STAFF",
                            "public" => 1
                        ],
                        [
                            "id" => 3,
                            "title" => "Item 3",
                            "parentId" => 77,
                            "description" => "Hey Mary",
                            "category" => "STAFF",
                            "public" => 1
                        ],
                        [
                            "id" => 4,
                            "title" => "Item 4",
                            "parentId" => 77,
                            "description" => "Hey Mary",
                            "category" => "STAFF",
                            "public" => 1
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_UPDATE
            ]

        ));


    }


    public function testIfConstantFieldValuesSuppliedAsPartOfConfigUsingReplaceMode() {

        $authService = Container::instance()->get(AuthenticationCredentialsService::class);
        /** @var SQLDatabaseCredentials $authCreds */
        $authCreds = $authService->getCredentialsInstanceByKey("test")->returnCredentials();
        Container::instance()->get(SecurityService::class)->becomeSuperUser();
        $dbConnection = $authCreds->returnDatabaseConnection();
        $dbConnection->executeScript(<<<SQL
DROP TABLE IF EXISTS _test_mapped_fields_parent;
CREATE TABLE _test_mapped_fields_parent (
    id INT,
    description VARCHAR(255),
    PRIMARY KEY (id, description)
);

DROP TABLE IF EXISTS _test_mapped_fields_child;
CREATE TABLE _test_mapped_fields_child (
    parentId INT,
    id INT,
    category VARCHAR(255),
    title VARCHAR(255),
    PRIMARY KEY (parentId, id)
);
INSERT INTO _test_mapped_fields_child (id, parentId, category, title) VALUES
(100, 55, 'STAFF', 'Old Item to delete'),
(200, 100, 'STAFF', 'Old Item to keep'),
(300, 55, 'STUDENTS', 'Old Item to keep');
SQL
        );

        /** @var BaseUpdatableDatasource $datasource */
        $datasource = ConcreteClassGenerator::instance()->generateInstance(BaseUpdatableDatasource::class);
        $datasourceService = Container::instance()->get(DatasourceService::class);
        $childTableDSI = new DatasourceInstance(
            "notes",
            "Notes",
            "sqldatabase",
            new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, "_test_mapped_fields_child"),
            "test",
        );
        $datasourceService->saveDataSourceInstance($childTableDSI);
        $datasource->setDatasourceService($datasourceService);
        $datasource->setValueFunctionEvaluator(Container::instance()->get(ValueFunctionEvaluator::class));

        $config = new DatasourceUpdateConfig(
            mappedFields: [
                new UpdatableMappedField("notes", "notes",
                    parentFieldMappings: [
                        "id" => "parentId",
//                        "[['STAFF']]" => "category"
                    ],
                    constantFieldValues: ["category" => "STAFF"],
                    updateMode: BaseUpdatableDatasource::UPDATE_MODE_REPLACE
                )
            ]
        );
        $datasource->setUpdateConfig($config);

        // Update mapped field data
        $dataSet = $datasource->updateMappedFieldData(
            new ArrayTabularDataset(
                [
                    new Field("id"),
                    new Field("description"),
                    new Field("notes")
                ],
                [
                    [
                        "id" => 55,
                        "description" => "Hey Bob",
                        "notes" => [
                            [
                                "id" => 1,
                                "title" => "Item 1"
                            ],
                            [
                                "id" => 2,
                                "title" => "Item 2"
                            ]
                        ]
                    ],
                    [
                        "id" => 77,
                        "description" => "Hey Mary",
                        "notes" => [
                            [
                                "id" => 3,
                                "title" => "Item 3"
                            ],
                        ]
                    ]
                ]
            ), BaseUpdatableDatasource::UPDATE_MODE_REPLACE
        );

        $parentData = $dbConnection->query(<<<SQL
SELECT * FROM _test_mapped_fields_parent;
SQL
        )->fetchAll();

        // Check data pruned
        $this->assertEquals([new Field("id"), new Field("description")], $dataSet->getColumns());

        // Check existing entries in the child table are replaced
        $expectedChildData = [
            [
                "id" => 1,
                "title" => "Item 1",
                "parentId" => 55,
                "category" => "STAFF",
            ],
            [
                "id" => 2,
                "title" => "Item 2",
                "parentId" => 55,
                "category" => "STAFF",
            ],
            [
                "id" => 3,
                "title" => "Item 3",
                "parentId" => 77,
                "category" => "STAFF",
            ],
            [
                "id" => 200,
                "title" => "Old Item to keep",
                "parentId" => 100,
                "category" => "STAFF",
            ],
            [
                "id" => 300,
                "title" => "Old Item to keep",
                "parentId" => 55,
                "category" => "STUDENTS",
            ]
        ];

        $childData = $dbConnection->query(<<<SQL
SELECT * FROM _test_mapped_fields_child;
SQL
)->fetchAll();

        usort($expectedChildData, fn($x, $y) => $x["id"] <=> $y["id"]);
        usort($childData, fn($x, $y) => $x["id"] <=> $y["id"]);
        $this->assertEquals($expectedChildData, $childData);
    }


    public function testIfUpdateModeSuppliedInUpdateConfigThisOverridesPassedMode() {
        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [], [], [], BaseUpdatableDatasource::UPDATE_MODE_ADD)
        ]);

        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"), new Field("notes")], [
            [
                "id" => 1,
                "notes" => [
                    [
                        "id" => 1,
                        "title" => "Item 1"
                    ],
                    [
                        "id" => 2,
                        "title" => "Item 2"
                    ]
                ]
            ],
            [
                "id" => 2,
                "notes" => [
                    [
                        "id" => 3,
                        "title" => "Item 3"
                    ],
                    [
                        "id" => 4,
                        "title" => "Item 4"
                    ]
                ]
            ]]));


        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("id"),
                    new Field("title")
                ],
                    [
                        [
                            "id" => 1,
                            "title" => "Item 1"
                        ],
                        [
                            "id" => 2,
                            "title" => "Item 2"
                        ],
                        [
                            "id" => 3,
                            "title" => "Item 3"
                        ],
                        [
                            "id" => 4,
                            "title" => "Item 4"
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_ADD
            ]

        ));
    }


    public function testIfReplaceModeSuppliedWithParentFieldMappingsPreviousEntriesAreFirstSelectedAndDeletedForParentField() {

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [], ["id" => "parentId"], [], BaseUpdatableDatasource::UPDATE_MODE_REPLACE)
        ]);

        // Get filtered datasource
        $filteredDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);

        // Expect a call to the notes datasource with a transformation
        $this->notesDatasource->returnValue("applyTransformation", $filteredDatasource, [
            new FilterTransformation([], [
                new FilterJunction([
                    new Filter("[[parentId]]", 1, Filter::FILTER_TYPE_EQUALS)
                ], [], FilterJunction::LOGIC_AND),
                new FilterJunction([
                    new Filter("[[parentId]]", 2, Filter::FILTER_TYPE_EQUALS)
                ], [], FilterJunction::LOGIC_AND)
            ], FilterJunction::LOGIC_OR)
        ]);
        $filteredDatasource->returnValue("materialise", new ArrayTabularDataset([
            new Field("id"),
            new Field("title"),
            new Field("parentId")
        ], [
            [
                "parentId" => 1,
                "id" => 10,
                "title" => "Item 10"
            ],
            [
                "parentId" => 1,
                "id" => 20,
                "title" => "Item 20"
            ],
            [
                "parentId" => 2,
                "id" => 30,
                "title" => "Item 30"
            ],
            [
                "parentId" => 2,
                "id" => 40,
                "title" => "Item 40"
            ]
        ]));


        $this->datasource->setUpdateConfig($config);

        // Update mapped field data
        $this->datasource->updateMappedFieldData(new ArrayTabularDataset([new Field("id"), new Field("notes")], [
            [
                "id" => 1,
                "notes" => [
                    [
                        "id" => 1,
                        "title" => "Item 1"
                    ],
                    [
                        "id" => 2,
                        "title" => "Item 2"
                    ]
                ]
            ],
            [
                "id" => 2,
                "notes" => [
                    [
                        "id" => 3,
                        "title" => "Item 3"
                    ],
                    [
                        "id" => 4,
                        "title" => "Item 4"
                    ]
                ]
            ]]), BaseUpdatableDatasource::UPDATE_MODE_REPLACE);


        // Expect a delete to occur for the existing data
        $this->assertTrue($this->notesDatasource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("id"),
                new Field("title"),
                new Field("parentId")
            ], [
                [
                    "parentId" => 1,
                    "id" => 10,
                    "title" => "Item 10"
                ],
                [
                    "parentId" => 1,
                    "id" => 20,
                    "title" => "Item 20"
                ],
                [
                    "parentId" => 2,
                    "id" => 30,
                    "title" => "Item 30"
                ],
                [
                    "parentId" => 2,
                    "id" => 40,
                    "title" => "Item 40"
                ]
            ]),
            BaseUpdatableDatasource::UPDATE_MODE_DELETE
        ]));

        // Expect an ADD rather than a replace as we have cleared old data out of the way.
        $this->assertTrue($this->notesDatasource->methodWasCalled("update",
            [
                new ArrayTabularDataset([
                    new Field("id"),
                    new Field("title"),
                    new Field("parentId")
                ],
                    [
                        [
                            "parentId" => 1,
                            "id" => 1,
                            "title" => "Item 1"
                        ],
                        [
                            "parentId" => 1,
                            "id" => 2,
                            "title" => "Item 2"
                        ],
                        [
                            "parentId" => 2,
                            "id" => 3,
                            "title" => "Item 3"
                        ],
                        [
                            "parentId" => 2,
                            "id" => 4,
                            "title" => "Item 4"
                        ]
                    ]),
                BaseUpdatableDatasource::UPDATE_MODE_REPLACE
            ]

        ));


    }

    public function testFlattenChildFields(){
        $mappedField = new UpdatableMappedField(
            "notes",
            "notes",
            parentFieldMappings:["id" => "parentId"],
            updateMode: BaseUpdatableDatasource::UPDATE_MODE_REPLACE,
            flattenFieldMappings: ["tags" => "tag"],
        );

        $expectedData = [
            ["parentId" => 7, "id" => 8, "tag" => "Fruits"],
            ["parentId" => 7, "id" => 8, "tag" => "Vegetables"],
            ["parentId" => 7, "id" => 9, "tag" => "Fruits"],
        ];

        $inputData = [
            [
                "id" => 7,
                "notes" => [
                    [
                        "id" => 8,
                        "tags" => ["Fruits", "Vegetables"]
                    ],
                    [
                        "id" => 9,
                        "tags" => ["Fruits"]
                    ]
                ]
            ]
        ];

        $mappedData = BaseUpdatableDatasource::getMappedData($inputData, $mappedField, $this->valueFunctionEvaluator);

        $this->assertEquals($expectedData, $mappedData);

        $inputData = [
            "id" => 7,
            "notes" => []
        ];

        $expectedData = [];
        $mappedData = BaseUpdatableDatasource::getMappedData($inputData, $mappedField, $this->valueFunctionEvaluator);
        $this->assertEquals($expectedData, $mappedData);
    }

    public function testFlattenFieldsWorksWithImplicitIndexArgument(){
        $mappedField = new UpdatableMappedField(
            "notes",
            "notes",
            parentFieldMappings:[
                "id" => "parentId",
                "_index" => "notes_idx"
            ],
            updateMode: BaseUpdatableDatasource::UPDATE_MODE_REPLACE,
            flattenFieldMappings: ["tags" => "tag"],
        );
        $inputData = [
            [
                "id" => 7,
                "notes" => []
            ]
        ];
        $expectedData = [];
        $mappedData = BaseUpdatableDatasource::getMappedData($inputData, $mappedField, $this->valueFunctionEvaluator);
        $this->assertEquals($expectedData, $mappedData);

        $inputData = [
            [
                "id" => 7,
                "notes" => [
                    [
                        "id" => 1,
                        "tags" => ["Fruits", "Vegetables"]
                    ]
                ]
            ]
        ];
        $expectedData = [
            [
                "parentId" => 7,
                "id" => 1,
                "tag" => "Fruits",
                "notes_idx" => 0
            ],
            [
                "parentId" => 7,
                "id" => 1,
                "tag" => "Vegetables",
                "notes_idx" => 0
            ]
        ];
        $mappedData = BaseUpdatableDatasource::getMappedData($inputData, $mappedField, $this->valueFunctionEvaluator);
        $this->assertEquals($expectedData, $mappedData);
    }

}