<?php

namespace Kinintel\Test\Objects\Dataset\Tabular;

use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\MultiTabularDataset;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class MultiTabularDatasetTest extends TestBase {
    public function testCanCreateSingleton(){
        $cols = [
            new Field("name"),
            new Field("age"),
        ];
        $multiset = new MultiTabularDataset([new ArrayTabularDataset($cols, [
            [
                "name" => "Joe",
                "age" => 83
            ]
        ])], $cols);

        $this->assertEquals([["name" => "Joe","age" => 83]], $multiset->getAllData());
    }

    public function testCanCreateWithValueExpressions(){
        $cols = [
            new Field("name"),
            new Field("age", valueExpression: 100),
        ];
        $multiset = new MultiTabularDataset(
            [
                new ArrayTabularDataset($cols, [
                    [
                        "name" => "Joe",
                        "age" => 83
                    ]
                ]),
                new ArrayTabularDataset([], []),
                new ArrayTabularDataset([
                    new Field("name"),
                    new Field("age", valueExpression: 101),
                ], [
                    [
                        "name" => "Joe",
                        "age" => 93
                    ]
                ])
            ],
            [
                new Field("name", valueExpression: "Bernie"),
                new Field("age")
            ]
        );

        $this->assertEquals([
            ["name" => "Bernie","age" => 100],
            ["name" => "Bernie","age" => 101],
        ], $multiset->getAllData());
    }
}