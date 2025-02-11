<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\Util;


use Google\Service\Docs\Tab;
use Kinikit\Persistence\Database\MetaData\TableColumn;
use Kinikit\Persistence\Database\MetaData\TableIndexColumn;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLColumnFieldMapper;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class SQLColumnFieldMapperTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var SQLColumnFieldMapper $mapper
     */
    private $mapper;

    public function setUp(): void {
        $this->mapper = new SQLColumnFieldMapper();
    }


    public function testCanMapSQLColumnsToFields() {

        // Check basic types
        $this->assertEquals(new Field("id", null, null, Field::TYPE_INTEGER),
            $this->mapper->mapResultSetColumnToField(new TableColumn("id", TableColumn::SQL_INTEGER)));

        $this->assertEquals(new Field("float", null, null, Field::TYPE_FLOAT),
            $this->mapper->mapResultSetColumnToField(new TableColumn("float", TableColumn::SQL_FLOAT)));

        $this->assertEquals(new Field("double", null, null, Field::TYPE_FLOAT),
            $this->mapper->mapResultSetColumnToField(new TableColumn("double", TableColumn::SQL_DOUBLE)));

        $this->assertEquals(new Field("real", null, null, Field::TYPE_FLOAT),
            $this->mapper->mapResultSetColumnToField(new TableColumn("real", TableColumn::SQL_REAL)));

        $this->assertEquals(new Field("decimal", null, null, Field::TYPE_FLOAT),
            $this->mapper->mapResultSetColumnToField(new TableColumn("decimal", TableColumn::SQL_DECIMAL)));


        $this->assertEquals(new Field("boolean", null, null, Field::TYPE_BOOLEAN),
            $this->mapper->mapResultSetColumnToField(new TableColumn("boolean", TableColumn::SQL_TINYINT)));

        $this->assertEquals(new Field("date", null, null, Field::TYPE_DATE),
            $this->mapper->mapResultSetColumnToField(new TableColumn("date", TableColumn::SQL_DATE)));

        $this->assertEquals(new Field("datetime", null, null, Field::TYPE_DATE_TIME),
            $this->mapper->mapResultSetColumnToField(new TableColumn("datetime", TableColumn::SQL_DATE_TIME)));

        $this->assertEquals(new Field("blob", null, null, Field::TYPE_LONG_STRING),
            $this->mapper->mapResultSetColumnToField(new TableColumn("blob", TableColumn::SQL_BLOB)));

        $this->assertEquals(new Field("longblob", null, null, Field::TYPE_LONG_STRING),
            $this->mapper->mapResultSetColumnToField(new TableColumn("longblob", TableColumn::SQL_LONGBLOB)));

        // Different sized VARCHARS
        $this->assertEquals(new Field("string", null, null, Field::TYPE_STRING),
            $this->mapper->mapResultSetColumnToField(new TableColumn("string", TableColumn::SQL_VARCHAR)));

        $this->assertEquals(new Field("string", null, null, Field::TYPE_STRING),
            $this->mapper->mapResultSetColumnToField(new TableColumn("string", TableColumn::SQL_VARCHAR, 20)));

        $this->assertEquals(new Field("string", null, null, Field::TYPE_STRING),
            $this->mapper->mapResultSetColumnToField(new TableColumn("string", TableColumn::SQL_VARCHAR, 255)));


        $this->assertEquals(new Field("string", null, null, Field::TYPE_MEDIUM_STRING),
            $this->mapper->mapResultSetColumnToField(new TableColumn("string", TableColumn::SQL_VARCHAR, 256)));

        $this->assertEquals(new Field("string", null, null, Field::TYPE_MEDIUM_STRING),
            $this->mapper->mapResultSetColumnToField(new TableColumn("string", TableColumn::SQL_VARCHAR, 2000)));

        $this->assertEquals(new Field("string", null, null, Field::TYPE_LONG_STRING),
            $this->mapper->mapResultSetColumnToField(new TableColumn("string", TableColumn::SQL_VARCHAR, 2001)));


        // Check primary key fields are mapped to key field as well if set
        $this->assertEquals(new Field("id", null, null, Field::TYPE_INTEGER, true, true),
            $this->mapper->mapResultSetColumnToField(new TableColumn("id", TableColumn::SQL_INTEGER, null, null, null, true)));

        // Check auto increment integer fields are mapped to id fields
        $this->assertEquals(new Field("auto", null, null, Field::TYPE_ID, true, true),
            $this->mapper->mapResultSetColumnToField(new TableColumn("auto", TableColumn::SQL_INTEGER, null, null, null, true, true)));


        // Check required fields
        $this->assertEquals(new Field("test", null, null, Field::TYPE_STRING, false, true),
            $this->mapper->mapResultSetColumnToField(new TableColumn("test", TableColumn::SQL_VARCHAR, 255, null, null, false, false, true)));

    }

    public function testCanMapFieldsToColumns() {

        // ID Type
        $this->assertEquals(new TableColumn("id", TableColumn::SQL_INTEGER, 11, null, null, true, true),
            $this->mapper->mapFieldToTableColumn(new Field("id", null, null, Field::TYPE_ID)));

        // Standard types
        $this->assertEquals(new TableColumn("integer", TableColumn::SQL_INTEGER),
            $this->mapper->mapFieldToTableColumn(new Field("integer", null, null, Field::TYPE_INTEGER)));

        $this->assertEquals(new TableColumn("float", TableColumn::SQL_FLOAT),
            $this->mapper->mapFieldToTableColumn(new Field("float", null, null, Field::TYPE_FLOAT)));

        $this->assertEquals(new TableColumn("boolean", TableColumn::SQL_TINYINT),
            $this->mapper->mapFieldToTableColumn(new Field("boolean", null, null, Field::TYPE_BOOLEAN)));


        $this->assertEquals(new TableColumn("date", TableColumn::SQL_DATE),
            $this->mapper->mapFieldToTableColumn(new Field("date", null, null, Field::TYPE_DATE)));

        $this->assertEquals(new TableColumn("datetime", TableColumn::SQL_DATE_TIME),
            $this->mapper->mapFieldToTableColumn(new Field("datetime", null, null, Field::TYPE_DATE_TIME)));


        // String types
        $this->assertEquals(new TableColumn("string", TableColumn::SQL_VARCHAR, 255),
            $this->mapper->mapFieldToTableColumn(new Field("string", null, null, Field::TYPE_STRING)));
        $this->assertEquals(new TableColumn("mediumstring", TableColumn::SQL_VARCHAR, 2000),
            $this->mapper->mapFieldToTableColumn(new Field("mediumstring", null, null, Field::TYPE_MEDIUM_STRING)));
        $this->assertEquals(new TableColumn("longstring", TableColumn::SQL_LONGBLOB),
            $this->mapper->mapFieldToTableColumn(new Field("longstring", null, null, Field::TYPE_LONG_STRING)));


        // Required fields
        $this->assertEquals(new TableColumn("string", TableColumn::SQL_VARCHAR, 255, null, null, false, false, true),
            $this->mapper->mapFieldToTableColumn(new Field("string", null, null, Field::TYPE_STRING, false, true)));


    }

    public function testCanMapFieldsToIndexColumns() {
        // ID Type
        $this->assertEquals(new TableIndexColumn("id"),
            $this->mapper->mapFieldToIndexColumn(new Field("id", null, null, Field::TYPE_ID)));

        // Standard types
        $this->assertEquals(new TableIndexColumn("integer"),
            $this->mapper->mapFieldToIndexColumn(new Field("integer", null, null, Field::TYPE_INTEGER)));

        $this->assertEquals(new TableIndexColumn("float"),
            $this->mapper->mapFieldToIndexColumn(new Field("float", null, null, Field::TYPE_FLOAT)));

        $this->assertEquals(new TableIndexColumn("boolean"),
            $this->mapper->mapFieldToIndexColumn(new Field("boolean", null, null, Field::TYPE_BOOLEAN)));


        $this->assertEquals(new TableIndexColumn("date"),
            $this->mapper->mapFieldToIndexColumn(new Field("date", null, null, Field::TYPE_DATE)));

        $this->assertEquals(new TableIndexColumn("datetime"),
            $this->mapper->mapFieldToIndexColumn(new Field("datetime", null, null, Field::TYPE_DATE_TIME)));


        // String types
        $this->assertEquals(new TableIndexColumn("string"),
            $this->mapper->mapFieldToIndexColumn(new Field("string", null, null, Field::TYPE_STRING)));
        $this->assertEquals(new TableIndexColumn("mediumstring", 500),
            $this->mapper->mapFieldToIndexColumn(new Field("mediumstring", null, null, Field::TYPE_MEDIUM_STRING)));
        $this->assertEquals(new TableIndexColumn("longstring", 500),
            $this->mapper->mapFieldToIndexColumn(new Field("longstring", null, null, Field::TYPE_LONG_STRING)));

    }


}