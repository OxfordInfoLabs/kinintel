<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\MetaData\ResultSetColumn;
use Kinikit\Persistence\Database\MetaData\TableColumn;
use Kinikit\Persistence\Database\ResultSet\ResultSet;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class SQLResultSetTabularDatasetTest extends \PHPUnit\Framework\TestCase {


    public function testIfColumnsAreConstructedFieldTypesAreUpdatedFromResultSet() {

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $resultSet->returnValue("getColumns", [
            new ResultSetColumn("id", TableColumn::SQL_INTEGER),
            new ResultSetColumn("name", TableColumn::SQL_VARCHAR, 256),
            new ResultSetColumn("date_of_birth", TableColumn::SQL_DATE_TIME)
        ]);

        $dataset = new SQLResultSetTabularDataset($resultSet, [
            new Field("id", null, null, Field::TYPE_FLOAT),
            new Field("name", null, null, Field::TYPE_STRING),
            new Field("date_of_birth", null, null, Field::TYPE_LONG_STRING)
        ]);

        $this->assertEquals([
            new Field("id", null, null, Field::TYPE_INTEGER),
            new Field("name", null, null, Field::TYPE_MEDIUM_STRING),
            new Field("date_of_birth", null, null, Field::TYPE_DATE_TIME)
        ], $dataset->getColumns());

    }


    public function testColumnsAreGeneratedFromColumnMetaInResultSetIfNotSuppliedOnConstruction() {

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);

        $resultSet->returnValue("getColumns", [
            new ResultSetColumn("id", TableColumn::SQL_INTEGER),
            new ResultSetColumn("name", TableColumn::SQL_VARCHAR, 256),
            new ResultSetColumn("date_of_birth", TableColumn::SQL_DATE_TIME)
        ]);

        $dataset = new SQLResultSetTabularDataset($resultSet);

        $this->assertEquals([
            new Field("id", null, null, Field::TYPE_INTEGER),
            new Field("name", null, null, Field::TYPE_MEDIUM_STRING),
            new Field("date_of_birth", null, null, Field::TYPE_DATE_TIME)
        ], $dataset->getColumns());


    }


    public function testNextDataItemSimplyCallsNextOnResultSet() {

        $resultSet = MockObjectProvider::instance()->getMockInstance(ResultSet::class);
        $resultSet->returnValue("getColumns", [
            new TableColumn("id", TableColumn::SQL_INTEGER),
            new TableColumn("name", TableColumn::SQL_VARCHAR),
            new TableColumn("date_of_birth", TableColumn::SQL_DATE)
        ]);
        $resultSet->returnValue("nextRow", [
            "id" => 4,
            "name" => "Bob"
        ]);

        $dataset = new SQLResultSetTabularDataset($resultSet);

        $nextItem = $dataset->nextDataItem();
        $this->assertEquals([
            "id" => 4,
            "name" => "Bob",
            "date_of_birth" => null
        ], $nextItem);

    }


}