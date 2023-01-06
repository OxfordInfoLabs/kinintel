<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster;

use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\EuclideanMetricCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\MetricCalculator;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\PearsonCorrelationMetricCalculator;
use Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis\KMeansClusterConfiguration;
use Kinintel\ValueObjects\Dataset\Field;

class KMeansCluster {

    /**
     * @param KMeansClusterConfiguration $config
     * @param TabularDataset $dataSet
     * @param MetricCalculator $calculator
     * @return ArrayTabularDataset
     */
    public function process($config, $sourceDataset, $calculator) {
        $sourceData = $sourceDataset->getAllData();

        $keyToId = []; //Goes from keyname to index
        $IdToKey = []; //Goes from index to keyname
        $maxDist = []; //Key -> MaxDistanceToThisElement
        $buckets = []; //Array of the k cluster buckets containing their distance to all the elements
        $distanceVectors = []; //Array of the distance from each individual element to each other element
        $fieldOneName = $sourceDataset->getColumns()[0]->getName();
        $fieldTwoName = $sourceDataset->getColumns()[1]->getName();
        $valueFieldName = $sourceDataset->getColumns()[2]->getName();
        $totalIDs = 0;

        // Iterate through the rows
        for ($i = 0; $i < count($sourceData); $i++) {
            $key = $sourceData[$i][$fieldOneName];
            $key2 = $sourceData[$i][$fieldTwoName];
            $val = $sourceData[$i][$valueFieldName];

            //Add the key to the keyToId array if it's not in there
            if (!array_key_exists($key, $keyToId)) {
                $keyToId[$key] = $totalIDs;
                $IdToKey[$totalIDs] = $key;
                $totalIDs++;
            }

            if (!array_key_exists($key2, $keyToId)) {
                $keyToId[$key2] = $totalIDs;
                $IdToKey[$totalIDs] = $key2;
                $totalIDs++;
            }

            // Set the distances
            $distanceVectors[$keyToId[$key]][$keyToId[$key2]] = $val;

            // Update the max distance from this element to another element
            $maxDist[$keyToId[$key]] = max($maxDist[$keyToId[$key]] ?? 0, $val);
            $maxDist[$keyToId[$key2]] = max($maxDist[$keyToId[$key2]] ?? 0, $val);
        }

        // Generate k buckets
        for ($i = 0; $i < $config->getNumberOfClusters(); $i++) {
            for ($j = 0; $j < count($keyToId); $j++) {
                $buckets[$i][$j] = $maxDist[$j] * (rand(1, 999) / 1000); //Randomise the "position" (distance to each element) of the centroid within the bounding box of data
            }
        }

        /**
         * @var integer[] $lastMatches id -> centroid
         */
        $lastMatches = []; // Element -> Centroid The array of the closest centroid to each element in the last timestep

        /**
         * @var integer[] $bestMatches id => centroid
         */
        $bestMatches = []; // Element -> Centroid The array of the closest centroid to each element in the current timestep

        /**
         * @var integer[][] $centroidsChildren centroid => [id of children]
         */
        $centroidsChildren = [];

        for ($t = 0; $t < $config->getTimestepLimit(); $t++) {

            for ($c = 0; $c < count($buckets); $c++) {
                $centroidsChildren[$c] = []; //The array of buckets and their matching elements
            }

            //Find the closest centroid to each element.
            for ($j = 0; $j < $totalIDs; $j++) {
                $bestMatches[$j] = $this->bestBucket($distanceVectors[$j], $buckets, $calculator);

                $centroidsChildren[$bestMatches[$j]][] = $j; //Best matching centroid gets j as child
            }
            if ($bestMatches === $lastMatches) { //If there was no change since the last iteration
                break;
            } else {
                $lastMatches = $bestMatches;
            }

            for ($c = 0; $c < count($buckets); $c++) { //Foreach centroid
                if (count($centroidsChildren[$c]) > 0) { //If the centroid has any children
                    //Reset the centroid's distance vectors to zero
                    for ($n = 0; $n < count($keyToId); $n++) {
                        $buckets[$c][$n] = 0;
                    }
                    //Set the centroid's distance vectors to the average distance vector of their children
                    for ($m = 0; $m < count($centroidsChildren[$c]); $m++) { //Foreach child in of centroid c
                        for ($n = 0; $n < count($keyToId); $n++) { //For each element, add
                            $id = $centroidsChildren[$c][$m];
                            $buckets[$c][$n] += $distanceVectors[$id][$n];
                        }
                    }
                    for ($n = 0; $n < count($keyToId); $n++) { //For each element, divide the corresponding distvec component by the number of children
                        $buckets[$c][$n] /= count($centroidsChildren[$c]);
                    } //Centroid's distance vectors are set to average distance vector of its children

                } else { //Send this centroid to the nearest element
                    $nearest = 0;
                    for ($n = 0; $n < count($keyToId); $n++) {
                        $nearest = $buckets[$c][$n] < $buckets[$c][$nearest] ? $n : $nearest;
                    }
                    $buckets[$c] = $distanceVectors[$nearest];
                }
            }
        }

        $outputData = []; //The array to format the data and write to snapshot
        for ($c = 0; $c < count($buckets); $c++) {
            $outputData[$c]["id"] = $c;
            $outputData[$c]["elements"] = [];
            foreach ($centroidsChildren[$c] as $child) {
                $outputData[$c]["elements"][] = $IdToKey[$child];
            }
            $outputData[$c]["size"] = count($centroidsChildren[$c]);
        }

        return new ArrayTabularDataset([
            new Field("id"),
            new Field("elements"),
            new Field("size")
        ], $outputData);
    }

    /**
     * Takes in the distVec of an element, and the current buckets, and finds the closest bucket
     * @param float[] $distVec
     * @param MetricCalculator $calculator
     * @return int
     */
    private function bestBucket($distVec, $buckets, $calculator) {

        $leastDistance = null;
        $closestBucket = 0;

        for ($c = 0; $c < count($buckets); $c++) {
            $distance = $calculator->calculateDistance($distVec, $buckets[$c]);
            if (is_null($leastDistance) || $distance < $leastDistance) {
                $leastDistance = $distance;
                $closestBucket = $c;
            }
        }
        return $closestBucket;

    }


}