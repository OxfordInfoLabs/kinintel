<?php

namespace Kinintel\Services\DataProcessor\DatasourceImport;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Util\Logging\CodeTimer;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceChangeTrackingProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Transformation\Formula\Expression;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseExpression;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class TabularDatasourceChangeTrackingProcessor implements DataProcessor {


    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * Datasource service
     *
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;
    }


    /**
     * Get the config class for this processor
     *
     * @return string|void
     */
    public function getConfigClass() {
        return TabularDatasourceChangeTrackingProcessorConfiguration::class;
    }

    /**
     * Process this datasource
     *
     * @param DataProcessorInstance $instance
     *
     * @return void
     */
    public function process($instance) {

        // Initialise various variables
        /**
         * @var TabularDatasourceChangeTrackingProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        $sourceReadChunkSize = $config->getSourceReadChunkSize() ?? PHP_INT_MAX;
        $targetWriteChunkSize = $config->getTargetWriteChunkSize();

        $targetLatestDatasourceKey = $config->getTargetLatestDatasourceKey();
        $targetChangeDatasourceKey = $config->getTargetChangeDatasourceKey();

        $datasourceKeys = $config->getSourceDatasourceKeys();
        $targetSummaryDatasourceKey = $config->getTargetSummaryDatasourceKey();

        // Assuming all datasources have the same keys - would be daft otherwise
        $fieldKeys = $this->datasourceService->getEvaluatedDataSource($datasourceKeys[0], [], [], 0, 1)->getColumns();

        $setDate = new \DateTime();


        // Iterate through each source datasource
        foreach ($datasourceKeys as $datasourceKey) {
            $directory = Configuration::readParameter("files.root") . "/change_tracking_processors/" . $instance->getKey() . "/" . $datasourceKey;

            $newFile = $directory . "/new.txt";
            $previousFile = $directory . "/previous.txt";

            // If directory does not exist, create it.
            if (!file_exists($directory))
                mkdir($directory, 0777, true);

            // Delete new file if it exists and copy to previous
            if (file_exists($directory . "/new.txt")) {
                unlink($directory . "/new.txt");
            }

            if (!file_exists($directory . "/previous.txt")) {
                touch($directory . "/previous.txt");
            }

            // Create the new file
            $this->writeDatasourcesToFile($directory, "new.txt", $datasourceKey, $sourceReadChunkSize);

            // Track changes between the new and previous
            passthru("diff -N $previousFile $newFile | grep -E '^>' | sed -E 's/^> //' > $directory/adds.txt");
            passthru("diff -N $previousFile $newFile | grep -E '^<' | sed -E 's/^< //' > $directory/deletes.txt");

            // Identify and changes and write to the latest and changes tables
            $this->analyseChanges($fieldKeys, $directory, $setDate->format('Y-m-d H:i:s'), $targetLatestDatasourceKey, $targetChangeDatasourceKey, $targetWriteChunkSize);

            // Copy the new file across to the previous
            copy($directory . "/new.txt", $directory . "/previous.txt");
        }

        // Finally, add to the summary table given the new latest table
        if ($targetSummaryDatasourceKey) {
            $this->createSummary($targetLatestDatasourceKey, $targetSummaryDatasourceKey, $config->getSummaryFields(), $sourceReadChunkSize, $targetWriteChunkSize, $setDate);
        }
    }


    private function writeDatasourcesToFile($directory, $fileName, $datasourceKey, $sourceReadChunkSize) {

        $offset = 0;

        // Read the datasource in chunks
        do {
            $lineCount = 0;
            $evaluated = $this->datasourceService->getEvaluatedDataSource($datasourceKey, [], [], $offset, $sourceReadChunkSize);
            $nextItem = $evaluated->nextDataItem();

            if (!$nextItem) {
                return;
            }

            // For each chunk, format each entry and write to new.txt
            do {

                $nextLine = "";
                foreach ($nextItem as $key => $value) {
                    $nextLine .= $value . "#|!";
                }
                file_put_contents($directory . "/" . $fileName, substr($nextLine, 0, -3) . "\n", FILE_APPEND);
                $lineCount++;

                $nextItem = $evaluated->nextDataItem();

            } while ($nextItem);

            $offset += $sourceReadChunkSize;

        } while ($lineCount == $sourceReadChunkSize);

    }

    private function analyseChanges($fieldKeys, $directory, $setDate, $targetLatestDatasourceKey, $targetChangeDatasourceKey, $targetWriteChunkSize) {


        // Initialise some variables

        $trueAdds = [];
        $trueUpdates = [];

        $addCount = 0;
        $updateCount = 0;
        $delCount = 0;

        $keyFieldKeysAsIndex = [];
        $keyFieldCount = 0;


        // Identify the key fields

        foreach ($fieldKeys as $key) {
            if ($key->isKeyField()) {
                $keyFieldKeysAsIndex[] = $keyFieldCount;
            }
            $keyFieldCount++;
        }


        // Convert deletes.txt into an array with keys as the primary fields

        $deletedItems = [];

        $deleteFileStream = new ReadOnlyFileStream($directory . "/deletes.txt");

        while ($line = $deleteFileStream->readLine()) {
            $explodedLine = explode("#|!", $line);
            if (sizeof($explodedLine) > 1) {
                $pkElements = [];
                foreach ($keyFieldKeysAsIndex as $key) {
                    $pkElements[] = $explodedLine[$key] ?? "";
                }
                $deletedItems[implode("#|!", $pkElements)] = 1;
            }
        }

        $deleteFileItems = explode("\n", file_get_contents($directory . "/deletes.txt"));

        // Iterate through adds.txt

        $addFileStream = new ReadOnlyFileStream($directory . "/adds.txt");
        while ($addLine = $addFileStream->readLine()) {

            // Ignore an empty line
            if (!$addLine) {
                continue;
            }

            // Explode the line
            $explodedAddLine = explode("#|!", $addLine);

            $pkElements = [];
            foreach ($keyFieldKeysAsIndex as $key) {
                $pkElements[] = $explodedAddLine[$key] ?? "";
            }
            $addKey = implode("#|!", $pkElements);


            // Identify if exists delete entry with same primary fields

            if (isset($deletedItems[$addKey])) {

                // If not a duplicate, it is an update
                if (!in_array($addLine, $deleteFileItems)) {
                    for ($j = 0; $j < sizeof($explodedAddLine); $j++) {
                        $trueUpdates[$updateCount][$fieldKeys[$j]->getName()] = trim($explodedAddLine[$j]) != "" ? trim($explodedAddLine[$j]) : null;
                    }
                    $updateCount++;
                }
                unset($deletedItems[$addKey]);

                // Carry out a datasource update once the batch size is reached
                if ($updateCount >= $targetWriteChunkSize) {
                    $this->updateTargetDatasources($trueUpdates, "UPDATE", $setDate, $targetLatestDatasourceKey, $targetChangeDatasourceKey);
                    $trueUpdates = [];
                    $updateCount = 0;
                }
                continue;
            }

            // Must be an add otherwise
            for ($i = 0; $i < sizeof($explodedAddLine); $i++) {
                if (isset($fieldKeys[$i])) {
                    $trueAdds[$addCount][$fieldKeys[$i]->getName()] = trim($explodedAddLine[$i]) != "" ? trim($explodedAddLine[$i]) : null;
                }
            }
            $addCount++;
            unset($deletedItems[$addKey]);


            // Update target datasources once batch size is reached
            if ($addCount >= $targetWriteChunkSize) {
                $this->updateTargetDatasources($trueAdds, "ADD", $setDate, $targetLatestDatasourceKey, $targetChangeDatasourceKey);
                $trueAdds = [];
                $addCount = 0;
            }


        }

        // Clear up any leftovers

        if ($addCount > 0) {
            $this->updateTargetDatasources($trueAdds, "ADD", $setDate, $targetLatestDatasourceKey, $targetChangeDatasourceKey);
        }
        if ($updateCount > 0) {
            $this->updateTargetDatasources($trueUpdates, "UPDATE", $setDate, $targetLatestDatasourceKey, $targetChangeDatasourceKey);
        }

        // Process the remaining delete items

        $deleteFileStream = new ReadOnlyFileStream($directory . "/deletes.txt");

        $trueDeletes = [];
        while ($line = $deleteFileStream->readLine()) {
            // Test whether the line still exists in the deleted items array
            $explodedLine = explode("#|!", $line);
            $pkElements = [];

            foreach ($keyFieldKeysAsIndex as $key) {
                $pkElements[] = $explodedLine[$key] ?? "";
            }

            if (isset($deletedItems[implode("#|!", $pkElements)])) {
                for ($i = 0; $i < sizeof($explodedLine); $i++) {
                    $trueDeletes[$delCount][$fieldKeys[$i]->getName()] = trim($explodedLine[$i]) != "" ? trim($explodedLine[$i]) : null;
                }
                $delCount++;
            }

            if ($delCount >= $targetWriteChunkSize) {
                $this->updateTargetDatasources($trueDeletes, "DELETE", $setDate, $targetLatestDatasourceKey, $targetChangeDatasourceKey);
                $trueDeletes = [];
                $delCount = 0;
            }

        }

        if ($delCount > 0)
            $this->updateTargetDatasources($trueDeletes, "DELETE", $setDate, $targetLatestDatasourceKey, $targetChangeDatasourceKey);
    }


    private function updateTargetDatasources($data, $updateType, $setDate, $targetLatestDatasourceKey, $targetChangeDatasourceKey) {

        // Initialise variables according to the update type
        $adds = $updateType == "ADD" ? $data : [];
        $updates = $updateType == "UPDATE" ? $data : [];
        $deletes = $updateType == "DELETE" ? $data : [];


        // Carry out the update to the latest table
        $datasourceLatestUpdate = new DatasourceUpdate([], $updates, $deletes, $adds);

        $this->datasourceService->updateDatasourceInstance($targetLatestDatasourceKey, $datasourceLatestUpdate, true);


        // Construct the changes update and update the changes table

        $changeType = new Field("change_type");
        $changeDate = new Field("change_date");

        for ($i = 0; $i < sizeof($data); $i++) {
            $data[$i][$changeType->getName()] = $updateType;
            $data[$i][$changeDate->getName()] = $setDate;
        }

        $datasourceChangesUpdate = new DatasourceUpdate([], [], [], $data);

        $this->datasourceService->updateDatasourceInstance($targetChangeDatasourceKey, $datasourceChangesUpdate, true);

    }

    private function createSummary($targetLatestDatasourceKey, $targetSummaryDatasourceKey, $summaryFields, $sourceReadChunkSize, $targetWriteChunkSize, $setDate) {


        /**
         * @var SQLResultSetTabularDataset $summarisedData
         */
        $summarisedData = $this->datasourceService->getEvaluatedDataSource($targetLatestDatasourceKey, [], [
            new TransformationInstance("formula", new FormulaTransformation([new Expression("Summary Date", "NOW()"),
                new Expression("Month", "MONTH(NOW())"),
                new Expression("Month Name", "MONTHNAME(NOW())"),
                new Expression("Year", "YEAR(NOW())")
            ])),
            new TransformationInstance("summarise", new SummariseTransformation(array_merge(["summaryDate", "month", "monthName", "year"], $summaryFields), [
                new SummariseExpression(SummariseExpression::EXPRESSION_TYPE_COUNT, null, null, "Total")
            ]))

        ]);

        $writeRows = [];
        while ($row = $summarisedData->nextDataItem()) {
            $writeRows[] = $row;
            if (sizeof($writeRows) == $targetWriteChunkSize) {
                $this->datasourceService->updateDatasourceInstance($targetSummaryDatasourceKey, new DatasourceUpdate($writeRows), true);
                $writeRows = [];
            }
        }

        if (sizeof($writeRows)) {
            $this->datasourceService->updateDatasourceInstance($targetSummaryDatasourceKey, new DatasourceUpdate($writeRows), true);
        }
    }

}