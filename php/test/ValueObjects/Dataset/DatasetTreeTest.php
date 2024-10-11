<?php

namespace Kinintel\Test\ValueObjects\Dataset;

use Kinintel\ValueObjects\Application\DataSearchItem;
use Kinintel\ValueObjects\Dataset\DatasetTree;

include_once "autoloader.php";

class DatasetTreeTest extends \PHPUnit\Framework\TestCase {

    public function testPropertiesMappedAsExpectedForPassedInput() {

        $datasetTree1 = new DatasetTree(new DataSearchItem("datasetinstance", 99, "Simple Dataset 1", "Simple Dataset Summary 1", "Sam Davis Design", null));
        $datasetTree2 = new DatasetTree(new DataSearchItem("datasetinstance", 98, "Simple Dataset 2", "Simple Dataset Summary 2", "Sam Davis Design", null));
        $datasetTree3 = new DatasetTree(new DataSearchItem("datasetinstance", 97, "Simple Dataset 3", "Simple Dataset Summary 3", "Sam Davis Design", null));
        $datasetTree4 = new DatasetTree(new DataSearchItem("datasetinstance", 96, "Simple Dataset 4", "Simple Dataset Summary 4", "Sam Davis Design", null),
            $datasetTree3, [$datasetTree2, $datasetTree1]);

        $this->assertEquals("datasetinstance", $datasetTree4->getType());
        $this->assertEquals("Simple Dataset 4", $datasetTree4->getTitle());
        $this->assertEquals("Simple Dataset Summary 4", $datasetTree4->getDescription());
        $this->assertEquals("Sam Davis Design", $datasetTree4->getOwningAccountName());
        $this->assertEquals($datasetTree3, $datasetTree4->getParentTree());
        $this->assertEquals([$datasetTree2, $datasetTree1], $datasetTree4->getJoinedTrees());
    }

}