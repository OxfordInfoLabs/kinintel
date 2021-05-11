<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\ResultSet\ResultSet;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class SQLResultSetTabularDatasetTest extends \PHPUnit\Framework\TestCase {


    public function testColumnsAreGeneratedFromColumnNamesInResultSet() {

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $resultSet->returnValue("getColumnNames", [
            "id", "name", "date_of_birth"
        ]);

        $dataset = new SQLResultSetTabularDataset($resultSet);

        $this->assertEquals([
            new Field("id"),
            new Field("name"),
            new Field("date_of_birth")
        ], $dataset->getColumns());


    }


    public function testNextDataItemSimplyCallsNextOnResultSet() {

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);
        $resultSet->returnValue("nextRow", [
            "id" => 4,
            "name" => "Bob"
        ]);

        $dataset = new SQLResultSetTabularDataset($resultSet);

        $nextItem = $dataset->nextDataItem();
        $this->assertEquals([
            "id" => 4,
            "name" => "Bob"
        ], $nextItem);

    }


}