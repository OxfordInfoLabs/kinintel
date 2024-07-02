<?php

namespace Kinintel\Services\DataProcessor\DatasourceImport;

use Exception;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Util\ArrayUtils;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinintel\Exception\InvalidDataProcessorConfigException;
use Kinintel\Exception\NoKeyFieldsException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\DataProcessor\BaseDataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\SourceDatasource;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport\TabularDatasourceChangeTrackingProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Formula\Expression;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseExpression;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class TabularDatasourceChangeTrackingProcessor extends BaseDataProcessor {

    public function __construct(
        private DatasourceService $datasourceService,
        private DatasetService $datasetService
    ) {
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
     * Process this datasource.
     *
     * This processor takes in a source dataset, which it will save to a text file (placed according to a config param)
     * If we have a .../previous.txt, we analyse changes and put them into the target datasources (usually tables).
     * We then place all the entries into .../previous.txt (to look at the next time)
     * @param DataProcessorInstance $instance
     *
     * @return void
     */
    public function process($instance) {

        // Initialise various variables
        /* @var TabularDatasourceChangeTrackingProcessorConfiguration $config */
        $config = $instance->returnConfig();

        $sourceReadChunkSize = $config->getSourceReadChunkSize() ?? PHP_INT_MAX;
        $targetWriteChunkSize = $config->getTargetWriteChunkSize();

        $setDate = new \DateTime();

        // Get sources, key fields
        /** @var (DatasetInstance|SourceDatasource)[] $sources */
        $sources = [];
        if ($config->getSourceDataset()) {
            $sourceDatasetInstance = $config->getSourceDataset();
            $sources[] = $sourceDatasetInstance;
            $fieldKeys = $this->datasetService
                ->getEvaluatedDataSetForDataSetInstance(
                    $sourceDatasetInstance,
                    offset: $config->getInitialOffset(), limit: 1)
                ->getColumns();
        } else if ($config->getSourceDatasources()) {
            $sourceDatasources = $config->getSourceDatasources();
            $sources = $sourceDatasources;
            $fieldKeys = $this->datasourceService
                ->getEvaluatedDataSourceByInstanceKey(
                    $sourceDatasources[0]->getDatasourceKey(),
                    $sourceDatasources[0]->getParameterSets()[0],
                    offset: $config->getInitialOffset(), limit: 1)
                ->getColumns();
        } else if ($config->getSourceDatasourceKeys()) {
            // Turn into sourceDatasource wrapper objects
            $datasourceKeys = $config->getSourceDatasourceKeys();
            $sources = array_map(
                fn($dsKey) => new SourceDatasource($dsKey, [[]]), // Run the datasource with no parameters
                $datasourceKeys);
            $fieldKeys = $this->datasourceService
                ->getEvaluatedDataSourceByInstanceKey(
                    $datasourceKeys[0], offset: $config->getInitialOffset(), limit: 1)
                ->getColumns();
        }

        $fieldKeys = $this->setCustomKeyFields($fieldKeys, $config->getCustomKeyFieldNames());

        foreach ($sources as $source) {
            if ($source instanceof DatasetInstance){
                $directory = Configuration::readParameter("files.root") . "/change_tracking_processors/" . $instance->getKey();
                $newFile = $directory . "/new.txt";
                $previousFile = $directory . "/previous.txt";
                $this->initialiseFiles($directory);
                $this->writeDatasetToFile(
                    $source,
                    $directory,
                    "new.txt",
                    $sourceReadChunkSize
                );
            } else if ($source instanceof SourceDatasource){
                $directory = Configuration::readParameter("files.root") . "/change_tracking_processors/" . $instance->getKey() . "/" . $source->getDatasourceKey();
                $newFile = $directory . "/new.txt";
                $previousFile = $directory . "/previous.txt";
                $this->initialiseFiles($directory);
                print_r($directory);
                echo "\n";
                foreach ($source->getParameterSets() as $parameterValues){
                    $this->writeDatasourcesToFile(
                        $directory,
                        "new.txt",
                        $source->getDatasourceKey(),
                        $sourceReadChunkSize,
                        $config->getOffsetField(),
                        $config->getInitialOffset(),
                        $parameterValues
                    );
                }
            } else {
                throw new InvalidDataProcessorConfigException([new FieldValidationError(errorMessage: "ChangeTracker can only track datasets and datasources, ".gettype($source)." passed in.")]);
            }

            // Skip diff if we've got nothing back from the datasource
            if (!file_exists($directory . "/new.txt")) {
                continue;
            }

            // Track changes between the new and previous

            passthru("diff -N $previousFile $newFile | grep -aE '^>' | sed -E 's/^> //' > $directory/adds.txt");
            passthru("diff -N $previousFile $newFile | grep -aE '^<' | sed -E 's/^< //' > $directory/deletes.txt");

            // Identify and changes and write to the latest and changes tables
            $this->analyseChanges(
                $fieldKeys,
                $directory,
                $setDate->format('Y-m-d H:i:s'),
                $config->getTargetLatestDatasourceKey(),
                $config->getTargetChangeDatasourceKey(),
                $targetWriteChunkSize
            );

            // Copy the new file across to the previous
            if (file_exists($directory . "/new.txt")) {
                copy($directory . "/new.txt", $directory . "/previous.txt");
            }

        }


        $targetSummaryDatasourceKey = $config->getTargetSummaryDatasourceKey();

        // Finally, add to the summary table given the new latest table
        if ($targetSummaryDatasourceKey) {
            $this->createSummary($config->getTargetLatestDatasourceKey(), $targetSummaryDatasourceKey, $config->getSummaryFields(), $sourceReadChunkSize, $targetWriteChunkSize, $setDate);
        }

    }

    /**
     * @param Field[] $fields
     * @param string[] $fieldNamesToMakeKeyFields
     * @return Field[]
     * @throws Exception
     */
    private function setCustomKeyFields($fields, $fieldNamesToMakeKeyFields) {
        if ($fieldNamesToMakeKeyFields) {
            foreach ($fields as $field) {
                $field->setKeyField(in_array($field->getName(), $fieldNamesToMakeKeyFields));
            }
        }
        if (ArrayUtils::all(array_map(fn(Field $field) => ! $field->isKeyField(), $fields))) {
            throw new NoKeyFieldsException("No key fields set in the TabularDatasourceChangeTrackingProcessor configuration for or in the source datasources");
        }

        return $fields;
    }


    private function writeDatasourcesToFile($directory, $fileName, $datasourceKey, $sourceReadChunkSize, $offsetField, $initialOffset, $parameterValues = []) {
        $offset = $initialOffset;
        // Read the datasource in chunks


        do {
            $lineCount = -1;
            $evaluated = $this->datasourceService->getEvaluatedDataSourceByInstanceKey($datasourceKey, $parameterValues, [], $offset, $sourceReadChunkSize);

            // Initialise next
            $nextItem = null;
            $offsetFieldValue = 0;

            // For each chunk, format each entry and write to new.txt
            do {

                if ($nextItem) {
                    $nextLine = "";

                    foreach ($nextItem as $key => $value) {

                        // If an array or object, base64 encode
                        if (is_array($value) || is_object($value)) {
                            $value = "base64:" . base64_encode(json_encode($value));
                        }
                        $nextLine .= $value . "#|!";
                    }
                    file_put_contents($directory . "/" . $fileName, substr($nextLine, 0, -3) . "\n", FILE_APPEND);
                    $offsetFieldValue = $nextItem[$offsetField] ?? 0;
                }

                $lineCount++;

                $nextItem = $evaluated->nextDataItem();

            } while ($nextItem !== false);

            if ($offsetField) {
                $offset = $offsetFieldValue;
            } else {
                $offset += $sourceReadChunkSize;
            }

        } while ($lineCount >= $sourceReadChunkSize);

    }


    private function writeDatasetToFile($sourceDatasetInstance, $directory, $fileName, $sourceReadChunkSize) {
        $offset = 0;

        // Read the datasource in chunks
        do {
            $lineCount = 0;
            $sourceDataset = $this->datasetService->getEvaluatedDataSetForDataSetInstance($sourceDatasetInstance, [], null, $offset, $sourceReadChunkSize);

            $nextItem = $sourceDataset->nextDataItem();

            if (!$nextItem) {
                return;
            }

            // For each chunk, format each entry and write to new.txt
            do {

                $nextLine = "";
                foreach ($nextItem as $key => $value) {

                    // If an array or object, base64 encode
                    if (is_array($value) || is_object($value)) {
                        $value = "base64:" . base64_encode(json_encode($value));
                    }

                    $nextLine .= $value . "#|!";
                }
                file_put_contents($directory . "/" . $fileName, substr($nextLine, 0, -3) . "\n", FILE_APPEND);
                $lineCount++;

                $nextItem = $sourceDataset->nextDataItem();

            } while ($nextItem);

            $offset += $sourceReadChunkSize;

        } while ($lineCount >= $sourceReadChunkSize);

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
                        $value = trim($explodedAddLine[$j]) != "" ? trim($explodedAddLine[$j]) : "";
                        if (substr($value, 0, 7) == "base64:")
                            $value = json_decode(base64_decode(substr($value, 7)), true);

                        $trueUpdates[$updateCount][$fieldKeys[$j]->getName()] = $value;
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
                    $value = trim($explodedAddLine[$i]) != "" ? trim($explodedAddLine[$i]) : null;
                    if ($value !== null && (substr($value, 0, 7) == "base64:"))
                        $value = json_decode(base64_decode(substr($value, 7)), true);
                    $trueAdds[$addCount][$fieldKeys[$i]->getName()] = $value;
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
        if ($targetLatestDatasourceKey){
            $datasourceLatestUpdate = new DatasourceUpdate([], $updates, $deletes, $adds);
            $this->datasourceService->updateDatasourceInstanceByKey($targetLatestDatasourceKey, $datasourceLatestUpdate, true);
        }


        // Construct the changes update and update the changes table

        $changeType = new Field("change_type");
        $changeDate = new Field("change_date");

        for ($i = 0; $i < sizeof($data); $i++) {
            $data[$i][$changeType->getName()] = $updateType;
            $data[$i][$changeDate->getName()] = $setDate;
        }

        $datasourceChangesUpdate = new DatasourceUpdate([], [], [], $data);

        if ($targetChangeDatasourceKey) {
            $this->datasourceService->updateDatasourceInstanceByKey($targetChangeDatasourceKey, $datasourceChangesUpdate, true);
        }

    }

    private function createSummary($targetLatestDatasourceKey, $targetSummaryDatasourceKey, $summaryFields, $sourceReadChunkSize, $targetWriteChunkSize, $setDate) {

        // Create filters array
        $filters = array_map(function ($item) {
            return new Filter("[[" . $item . "]]", null, Filter::FILTER_TYPE_NOT_NULL);
        }, $summaryFields);

        /**
         * @var SQLResultSetTabularDataset $summarisedData
         */
        $summarisedData = $this->datasourceService->getEvaluatedDataSourceByInstanceKey($targetLatestDatasourceKey, [], [
            new TransformationInstance("filter", new FilterTransformation($filters)),
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
                $this->datasourceService->updateDatasourceInstanceByKey($targetSummaryDatasourceKey, new DatasourceUpdate($writeRows), true);
                $writeRows = [];
            }
        }

        if (sizeof($writeRows)) {
            $this->datasourceService->updateDatasourceInstanceByKey($targetSummaryDatasourceKey, new DatasourceUpdate($writeRows), true);
        }
    }

    /**
     * @param $directory
     * @return void
     */
    public function initialiseFiles($directory) {
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
    }

    public function onInstanceDelete($instance) {

    }

}