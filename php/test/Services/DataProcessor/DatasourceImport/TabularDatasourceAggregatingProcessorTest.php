<?php

namespace Kinintel\Test\Services\DataProcessor\DatasourceImport;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\DataProcessor\DatasourceImport\TabularDatasourceAggregatingProcessor;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceAggregatingProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceAggregatingSource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\Sort;
use Kinintel\ValueObjects\Transformation\TransformationInstance;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class TabularDatasourceAggregatingProcessorTest extends TestCase {

    /**
     * @var TabularDatasourceAggregatingProcessor
     */
    private $processor;

    /**
     * @var MockObject
     */
    private $datasourceService;

    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->processor = new TabularDatasourceAggregatingProcessor($this->datasourceService);
    }

    public function testCanDoSimpleCombineOfDatasources() {

        $config = new TabularDatasourceAggregatingProcessorConfiguration([
            new TabularDatasourceAggregatingSource("source1", ["date" => "date", "match" => "match", "other" => "other"], "date", "number1"),
            new TabularDatasourceAggregatingSource("source2", ["date" => "date", "match" => "match", "stuff" => "stuff"], "date", "number2")
        ], "target", ["match"]);

        $instance = new DataProcessorInstance("no", "need", "tabulardatasourceaggregating", $config);


        $dataset1 = new ArrayTabularDataset([new Field("date"), new Field("match"), new Field("other")], [
            [
                "date" => "2023-01-14 14:32:51",
                "match" => "ping",
                "other" => "nonsense"
            ], [
                "date" => "2023-01-14 14:31:45",
                "match" => "pong",
                "other" => "test"
            ]
        ]);

        $dataset2 = new ArrayTabularDataset([new Field("date"), new Field("match"), new Field("stuff")], [
            [
                "date" => "2023-01-14 13:34:01",
                "match" => "pang",
                "stuff" => "bingo"
            ], [
                "date" => "2023-01-13 07:23:12",
                "match" => "ping",
                "stuff" => "bongo"
            ]
        ]);

        $today = (new \DateTime("midnight"))->format("Y-m-d H:i:s");

        $filters = [new Filter("[[date]]", $today, "gte")];

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", $dataset1, ["source1", [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort("date", "desc")]))
        ]]);

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", $dataset2, ["source2", [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort("date", "desc")]))
        ]]);

        $expectedUpdate = new DatasourceUpdate([], [], [], [
            [
                "date" => "2023-01-14 14:32:51",
                "match" => "ping",
                "other" => "nonsense",
                "stuff" => "bongo",
                "number1" => true,
                "number2" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-14 14:32:51",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 2
            ], [
                "date" => "2023-01-14 14:31:45",
                "match" => "pong",
                "other" => "test",
                "number1" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-14 14:31:45",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 1
            ], [
                "date" => "2023-01-14 13:34:01",
                "match" => "pang",
                "stuff" => "bingo",
                "number2" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-14 13:34:01",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 1
            ]
        ]);

        $this->processor->process($instance);
        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", ["target", $expectedUpdate, true]));

    }

    public function testCanUseColumnMappingsCorrectly() {

        $config = new TabularDatasourceAggregatingProcessorConfiguration([
            new TabularDatasourceAggregatingSource("source1", [
                "date" => "date",
                "match" => "match",
                "column1" => "first_col1",
                "column2" => "first_col2"
            ], "date", "first"),
            new TabularDatasourceAggregatingSource("source2", [
                "date" => "date",
                "match" => "match",
                "column1" => "second_col1",
                "column2" => "second_col2"
            ], "date", "second")
        ], "target", ["match"]);

        $instance = new DataProcessorInstance("no", "need", "tabulardatasourceaggregating", $config);


        $dataset1 = new ArrayTabularDataset([new Field("date"), new Field("match"), new Field("column1"), new Field("column2")], [
            [
                "date" => "2023-01-14 14:32:51",
                "match" => "Steve",
                "column1" => "yes",
                "column2" => "sometimes"
            ], [
                "date" => "2023-01-14 14:31:45",
                "match" => "John",
                "column1" => "no",
                "column2" => "maybe"
            ]
        ]);

        $dataset2 = new ArrayTabularDataset([new Field("date"), new Field("match"), new Field("column1"), new Field("column2")], [
            [
                "date" => "2023-01-14 13:34:01",
                "match" => "John",
                "column1" => "sometimes",
                "column2" => "no"
            ], [
                "date" => "2023-01-13 07:23:12",
                "match" => "James",
                "column1" => "maybe",
                "column2" => "yes"
            ]
        ]);

        $today = (new \DateTime("midnight"))->format("Y-m-d H:i:s");

        $filters = [new Filter("[[date]]", $today, "gte")];

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", $dataset1, ["source1", [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort("date", "desc")]))
        ]]);

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", $dataset2, ["source2", [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort("date", "desc")]))
        ]]);

        $expectedUpdate = new DatasourceUpdate([], [], [], [
            [
                "date" => "2023-01-14 14:32:51",
                "match" => "Steve",
                "first_col1" => "yes",
                "first_col2" => "sometimes",
                "first" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-14 14:32:51",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 1,
                "second" => null,
                "second_col1" => null,
                "second_col2" => null
            ], [
                "date" => "2023-01-14 14:31:45",
                "match" => "John",
                "first_col1" => "no",
                "first_col2" => "maybe",
                "second_col1" => "sometimes",
                "second_col2" => "no",
                "first" => true,
                "second" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-14 14:31:45",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 2
            ], [
                "date" => "2023-01-13 07:23:12",
                "match" => "James",
                "second_col1" => "maybe",
                "second_col2" => "yes",
                "second" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-13 07:23:12",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 1
            ]
        ]);

        $this->processor->process($instance);

        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", ["target", $expectedUpdate, true]));

    }

    public function testCanCombineMoreThanTwoSources() {

        $config = new TabularDatasourceAggregatingProcessorConfiguration([
            new TabularDatasourceAggregatingSource("source1", ["date" => "date", "match" => "match", "other" => "other"], "date", "number1"),
            new TabularDatasourceAggregatingSource("source2", ["date" => "date", "match" => "match", "stuff" => "stuff"], "date", "number2"),
            new TabularDatasourceAggregatingSource("source3", ["date" => "date", "match" => "match", "stuff" => "stuff"], "date", "number3"),
        ], "target", ["match"]);

        $instance = new DataProcessorInstance("no", "need", "tabulardatasourceaggregating", $config);


        $dataset1 = new ArrayTabularDataset([new Field("date"), new Field("match"), new Field("other")], [
            [
                "date" => "2023-01-14 14:32:51",
                "match" => "eins",
                "other" => "nonsense"
            ], [
                "date" => "2023-01-14 06:00:00",
                "match" => "zwei",
                "other" => "test"
            ]
        ]);

        $dataset2 = new ArrayTabularDataset([new Field("date"), new Field("match"), new Field("stuff")], [
            [
                "date" => "2023-01-14 13:34:01",
                "match" => "zwei",
                "stuff" => "bingo"
            ]
        ]);

        $dataset3 = new ArrayTabularDataset([new Field("date"), new Field("match"), new Field("stuff")], [
            [
                "date" => "2023-01-14 13:34:01",
                "match" => "drei",
                "stuff" => "bong"
            ], [
                "date" => "2023-01-13 07:23:12",
                "match" => "zwei",
                "stuff" => "bingo"
            ]
        ]);

        $today = (new \DateTime("midnight"))->format("Y-m-d H:i:s");

        $filters = [new Filter("[[date]]", $today, "gte")];

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", $dataset1, ["source1", [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort("date", "desc")]))
        ]]);

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", $dataset2, ["source2", [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort("date", "desc")]))
        ]]);

        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey", $dataset3, ["source3", [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
            new TransformationInstance("multisort", new MultiSortTransformation([new Sort("date", "desc")]))
        ]]);

        $expectedUpdate = new DatasourceUpdate([], [], [], [
            [
                "date" => "2023-01-14 14:32:51",
                "match" => "eins",
                "other" => "nonsense",
                "number1" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-14 14:32:51",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 1,
                "number2" => null,
                "number3" => null,
                "stuff" => null
            ], [
                "date" => "2023-01-14 06:00:00",
                "match" => "zwei",
                "other" => "test",
                "stuff" => "bingo",
                "number1" => true,
                "number2" => true,
                "number3" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-14 13:34:01",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 3
            ], [
                "date" => "2023-01-14 13:34:01",
                "match" => "drei",
                "stuff" => "bong",
                "number3" => true,
                "window_time" => $today,
                "latest_discover_time" => "2023-01-14 13:34:01",
                "discover_month" => date_create_from_format("Y-m-d H:i:s", $today)->format("F"),
                "discover_month_index" => date_create_from_format("Y-m-d H:i:s", $today)->format("n"),
                "discover_year" => date_create_from_format("Y-m-d H:i:s", $today)->format("Y"),
                "sources_with_results" => 1
            ]
        ]);

        $this->processor->process($instance);

        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", ["target", $expectedUpdate, true]));

    }

}