<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\CustomDatasourceService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseExpression;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class EquationMetricProcessor implements MetricProcessor {
    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * @var DatasourceService
     */
    private $customDatasourceService;

    /**
     * @param DatasourceService $datasourceService
     * @param DatasetService $datasetService
     * @param CustomDatasourceService $customDatasourceService
     */
    public function __construct($datasourceService, $datasetService, $customDatasourceService) {
        $this->datasourceService = $datasourceService;
        $this->datasetService = $datasetService;
        $this->customDatasourceService = $customDatasourceService;
    }

    /**
     *
     * @param DataProcessorInstance $instance
     * @param MetricCalculator $calculator
     * @return DatasourceInstance
     */
    public function process($instance, $calculator) {
        $config = $instance->returnConfig();

        //Returns a "snapshot" type datasource
        $snapshotInstance = $this->customDatasourceService->createTabularSnapshotDatasourceInstance($instance->getTitle() . ": " . $calculator->getTitle() . " Distance",
            [
                new Field($config->getKeyFieldName()),
                new Field($config->getKeyFieldName() . "_2"),
                new Field("distance")
            ], $instance->getProjectKey(), $instance->getAccountId());

        $isSource = $config->getDatasourceKey() != null && $config->getDatasourceKey() != "";

        //Query to calculate distance from a Vector name (Key field), Component name (Component field), and Component value (Value field)
        $transformations = [

            //Join the two sources with respect to their phrases (and the condition that the first document's name comes alphabetically before).
            new TransformationInstance("join", new JoinTransformation($config->getDatasourceKey(), null, [], new FilterJunction([
                new Filter("[[" . $config->getComponentFieldName() . "]]", "[[" . $config->getComponentFieldName() . "]]", Filter::FILTER_TYPE_EQUALS),
                new Filter("[[" . $config->getKeyFieldName() . "]]", "[[" . $config->getKeyFieldName() . "]]", Filter::FILTER_TYPE_GREATER_THAN)
            ]), [new Field($config->getKeyFieldName()), new Field($config->getValueFieldName())], true)),

            //Summarise over the data with distance
            new TransformationInstance("summarise", new SummariseTransformation([$config->getKeyFieldName(), $config->getKeyFieldName() . "_2"], [
                new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_CUSTOM, null,
                    $calculator->getCustomExpression($config), "distance")
            ]))
        ];

        if ($isSource) { //Datasource Key provided
            $minimalDataset = $this->datasourceService->getEvaluatedDataSource($config->getDatasourceKey(), [], $transformations);
        } else { //No Datasource Key provided -> Default to Dataset ID
            $minimalDataset = $this->datasetService->getEvaluatedDataSetForDataSetInstanceById($config->getDatasetId(), [], $transformations, null, null);
        }

        // Insert the minimal dataset into the snapshot datasource
        $snapshotSource = $snapshotInstance->returnDataSource();

        $snapshotSource->update($minimalDataset, UpdatableDatasource::UPDATE_MODE_ADD);

        //Reset minimalDataset (TabularDataset) back to unread.
        $minimalDataset = $snapshotSource->materialise();
        $writeArray = [];
        $keys = [];

        //Complete the database with entries where the first document's name comes alphabetically after, and zero entries.
        while ($data = $minimalDataset->nextDataItem()) {
            if (!array_key_exists($data[$config->getKeyFieldName()], $keys)) {
                $writeArray[] = [
                    $config->getKeyFieldName() => $data[$config->getKeyFieldName()],
                    $config->getKeyFieldName() . "_2" => $data[$config->getKeyFieldName()],
                    "distance" => 0
                ];
                $keys[$data[$config->getKeyFieldName()]] = 1;
            }
            if (!array_key_exists($data[$config->getKeyFieldName() . "_2"], $keys)) {
                $writeArray[] = [
                    $config->getKeyFieldName() => $data[$config->getKeyFieldName() . "_2"],
                    $config->getKeyFieldName() . "_2" => $data[$config->getKeyFieldName() . "_2"],
                    "distance" => 0
                ];
                $keys[$data[$config->getKeyFieldName() . "_2"]] = 1;
            }

            $writeArray[] = [
                $config->getKeyFieldName() => $data[$config->getKeyFieldName() . "_2"],
                $config->getKeyFieldName() . "_2" => $data[$config->getKeyFieldName()],
                "distance" => $data["distance"]
            ];
        }

        $snapshotSource->update(
            new ArrayTabularDataset([
                new Field($config->getKeyFieldName()),
                new Field($config->getKeyFieldName() . "_2"),
                new Field("distance")
            ], $writeArray), UpdatableDatasource::UPDATE_MODE_ADD);

        return $snapshotInstance;
    }
}