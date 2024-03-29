<?php

namespace Kinintel\Services\Util\Analysis\StatisticalAnalysis\Cluster;

use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Services\Util\Analysis\StatisticalAnalysis\Distance\MetricCalculator;
use Kinintel\ValueObjects\Dataset\Field;

class HierarchicalCluster {

    /**
     * Process from a dataset
     *
     * @param TabularDataset $sourceDataset
     * @param MetricCalculator $calculator
     * @return ArrayTabularDataset
     */
    public function process($sourceDataset, $calculator) {
        $sourceData = $sourceDataset->getAllData();
        $clustArray = [];
        $keyToId = []; //Goes from index to keyname
        $fieldName1 = $sourceDataset->getColumns()[0]->getName();
        $fieldName2 = $sourceDataset->getColumns()[1]->getName();
        $valueFieldName = $sourceDataset->getColumns()[2]->getName();

        $k = 0;
        for ($i = 0; $i < count($sourceData); $i++) {
            if (!array_key_exists($sourceData[$i][$fieldName1], $keyToId)) {
                $keyToId[$sourceData[$i][$fieldName1]] = $k;
                $k++;
            }
        }

        $keys = array_flip($keyToId); //Goes from keyname to index
        $clusterCount = 0;
        for ($i = 0; $i < count($sourceData); $i++) { //For each row, initialise the leaf clusters
            $key = $sourceData[$i][$fieldName1];
            $key2 = $sourceData[$i][$fieldName2];
            $val = $sourceData[$i][$valueFieldName];

            if (!array_key_exists($keyToId[$key], $clustArray)) { //If clust array doesn't contain a leaf node for this key, add it
                $clustArray[$clusterCount] = ["id" => $clusterCount, "direct_children" => [], "distVec" => [$keyToId[$key2] => $val], "distance_to_parent" => 0, "elements" => [$key]];
                $clusterCount++; //To clarify, "distVec" is an array int -> float, of size keys.count, stating the distance from this cluster to the initial nodes.
            } else { //If this key already has a leaf node set its distance based on the data
                $clustArray[$keyToId[$key]]["distVec"][$keyToId[$key2]] = $val;
            }
        }

        $activeClusters = $clustArray;

        while (count($activeClusters) > 1) {
            $lowestPair = [0, 1];
            $closestDist = -1;

            // Calculate all pairwise distances - can certainly be optimised
            for ($i = 0; $i < count($activeClusters); $i++) {
                for ($j = $i + 1; $j < count($activeClusters); $j++) {
                    if ($activeClusters[$i]["direct_children"] == []) {
                        $d = $activeClusters[$j]["distVec"][$activeClusters[$i]["id"]];
                    } else if ($activeClusters[$j]["direct_children"] == []) {
                        $d = $activeClusters[$i]["distVec"][$activeClusters[$j]["id"]];
                    } else {
                        $d = 1 - $calculator->calculateDistance($activeClusters[$i]["distVec"], $activeClusters[$j]["distVec"]);
                    }

                    if ($d < $closestDist || $closestDist == -1) {
                        $closestDist = $d;
                        $lowestPair = [$i, $j];
                    }
                }
            }

            //Smallest distance found: It is $closestDist, which is the distance between $lowestPair[0] and $lowestPair[1]
            $c1 = $activeClusters[$lowestPair[0]];
            $c2 = $activeClusters[$lowestPair[1]];

            //Create a new cluster from the lowest pair
            $mergeDistVec = [];
            for ($i = 0; $i < count($keys); $i++) {
                $mergeDistVec[$i] = 0.5 * ($c1["distVec"][$i] + $c2["distVec"][$i]);
            }

            $newCluster = ["id" => $clusterCount, "direct_children" => [$c1["id"], $c2["id"]], "distVec" => $mergeDistVec, "distance_to_parent" => $closestDist,
                "elements" => array_merge($c1["elements"], $c2["elements"])];
            $clustArray[$clusterCount] = $newCluster;
            $clusterCount++;

            //Drop the lowest clusters of the lowest pair while maintaining indices
            unset($activeClusters[$lowestPair[0]]);
            unset($activeClusters[$lowestPair[1]]);

            $activeClusters = array_values($activeClusters); //Reindexes the array
            $activeClusters[] = $newCluster; //Adds the new cluster;
        }

        for ($i = 0; $i < count($clustArray); $i++) {
            unset($clustArray[$i]["distVec"]);
            unset($clustArray[$i]["distance_to_parent"]);
            $clustArray[$i]["size"] = count($clustArray[$i]["elements"]);
        }

        return new ArrayTabularDataset([
            new Field("id"),
            new Field("direct_children"),
            new Field("elements"),
            new Field("size")
        ], $clustArray);
    }

}