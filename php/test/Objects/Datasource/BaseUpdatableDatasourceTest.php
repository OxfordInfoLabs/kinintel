<?php


namespace Kinintel\ValueObjects\Datasource;

use Kinikit\Core\Testing\ConcreteClassGenerator;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseUpdatableDatasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

/**
 * Test cases for the base updatable datasource - especially the saving of mapped field data
 *
 * Class BaseUpdatableDatasourceTest
 * @package Kinintel\ValueObjects\Datasource
 */
class BaseUpdatableDatasourceTest extends \PHPUnit\Framework\TestCase {

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


    public function setUp(): void {
        $this->datasource = ConcreteClassGenerator::instance()->generateInstance(BaseUpdatableDatasource::class);

        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            $datasourceInstance);
        $this->notesDatasource = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $datasourceInstance->returnValue("returnDataSource", $this->notesDatasource);
        $this->datasource->setDatasourceService($this->datasourceService);
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


    public function testParentFieldDataMergedInToMappedDataIfDefinedInConfig() {

        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes",
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

        ]), BaseUpdatableDatasource::UPDATE_MODE_REPLACE);


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
                BaseUpdatableDatasource::UPDATE_MODE_REPLACE
            ]

        ));


    }

    public function testIfUpdateModeSuppliedInUpdateConfigThisOverridesPassedMode() {
        $config = new DatasourceUpdateConfig([], [
            new UpdatableMappedField("notes", "notes", [], BaseUpdatableDatasource::UPDATE_MODE_REPLACE)
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
                BaseUpdatableDatasource::UPDATE_MODE_REPLACE
            ]

        ));
    }


}