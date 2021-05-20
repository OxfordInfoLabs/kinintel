<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;

include_once "autoloader.php";

class PagingTransformationProcessorTest extends \PHPUnit\Framework\TestCase {

    public function testPagingTransformationProcessorCorrectlyAppliesOffsetAndLimits() {

        $processor = new PagingTransformationProcessor();

        $pagingTransformation = new PagingTransformation(100);
        $sqlQuery = new SQLQuery("*", "test_data");

        $resultQuery = $processor->updateQuery($pagingTransformation, $sqlQuery);
        $this->assertEquals("SELECT * FROM test_data LIMIT ?", $resultQuery->getSQL());
        $this->assertEquals([100], $resultQuery->getParameters());


        $pagingTransformation = new PagingTransformation(100, 50);
        $sqlQuery = new SQLQuery("*", "test_data");

        $resultQuery = $processor->updateQuery($pagingTransformation, $sqlQuery);
        $this->assertEquals("SELECT * FROM test_data LIMIT ? OFFSET ?", $resultQuery->getSQL());
        $this->assertEquals([100, 50], $resultQuery->getParameters());


    }

}