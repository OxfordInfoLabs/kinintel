<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\Util;

use Exception;
use Kinikit\Persistence\Database\MetaData\ResultSetColumn;
use Kinikit\Persistence\Database\MetaData\TableColumn;
use Kinikit\Persistence\Database\MetaData\TableIndexColumn;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\SQLDatabase\NonexistentColumnTypeException;

/**
 * Utility class to provide mapping of SQL Columns to Fields and vice versa
 *
 * Class SQLColumnFieldMapper
 * @package Kinintel\Objects\Datasource\SQLDatabase\Util
 */
class SQLColumnFieldMapper {

    /**
     * Mappings of Types to SQL types
     */
    const FIELD_TYPE_SQL_TYPE_MAP = [
        Field::TYPE_STRING => TableColumn::SQL_VARCHAR,
        Field::TYPE_MEDIUM_STRING => TableColumn::SQL_VARCHAR,
        Field::TYPE_INTEGER => TableColumn::SQL_INTEGER,
        Field::TYPE_FLOAT => TableColumn::SQL_FLOAT,
        Field::TYPE_DATE => TableColumn::SQL_DATE,
        Field::TYPE_DATE_TIME => TableColumn::SQL_DATE_TIME,
        Field::TYPE_ID => TableColumn::SQL_INTEGER,
        Field::TYPE_LONG_STRING => TableColumn::SQL_LONGBLOB
    ];

    /**
     * Map types which need qualifying with a max bytes for indexing purposes
     * i.e. Only index based on the first 500 chars of a VARCHAR(2000)
     */
    const FIELD_TYPE_INDEX_MAX_BYTES_MAP = [
        Field::TYPE_MEDIUM_STRING => 200,
        Field::TYPE_LONG_STRING => 200
    ];


    const FIELD_TYPE_LENGTH_MAP = [
        Field::TYPE_STRING => 255,
        Field::TYPE_MEDIUM_STRING => 2000,
        Field::TYPE_ID => 11
    ];

    // Mappings of SQL Types to Field
    const SQL_TYPE_FIELD_TYPE_MAP = [
        TableColumn::SQL_DOUBLE => Field::TYPE_FLOAT,
        TableColumn::SQL_DATE_TIME => Field::TYPE_DATE_TIME,
        TableColumn::SQL_DATE => Field::TYPE_DATE,
        TableColumn::SQL_INT => Field::TYPE_INTEGER,
        TableColumn::SQL_VARCHAR => [
            0 => Field::TYPE_STRING,
            256 => Field::TYPE_MEDIUM_STRING,
            2001 => Field::TYPE_LONG_STRING
        ],
        TableColumn::SQL_BIGINT => Field::TYPE_INTEGER,
        TableColumn::SQL_BLOB => Field::TYPE_LONG_STRING,
        TableColumn::SQL_LONGBLOB => Field::TYPE_LONG_STRING,
        TableColumn::SQL_DECIMAL => Field::TYPE_FLOAT,
        TableColumn::SQL_REAL => Field::TYPE_FLOAT,
        TableColumn::SQL_FLOAT => Field::TYPE_FLOAT,
        TableColumn::SQL_SMALLINT => Field::TYPE_INTEGER,
        TableColumn::SQL_INTEGER => Field::TYPE_INTEGER,
        TableColumn::SQL_TIME => Field::TYPE_INTEGER,
        TableColumn::SQL_TIMESTAMP => Field::TYPE_DATE_TIME,
        TableColumn::SQL_UNKNOWN => Field::TYPE_STRING
    ];


    /**
     * Map a field to a table column - DEFAULTS TO VARCHAR 255 IF NO TYPE GIVEN
     *
     * @param Field $field
     * @return TableColumn
     */
    public function mapFieldToTableColumn($field) {

        // Derive the type - DEFAULTS TO VARCHAR 255
        $fieldType = $field->getType() ?? Field::TYPE_STRING;
        $type = self::FIELD_TYPE_SQL_TYPE_MAP[$fieldType] ?? TableColumn::SQL_VARCHAR;

        // Lookup length or infer
        $length = self::FIELD_TYPE_LENGTH_MAP[$fieldType] ?? null;

        // Primary key
        $primaryKey = $field->isKeyField() || ($fieldType == Field::TYPE_ID);
        $autoIncrement = ($fieldType == Field::TYPE_ID);

        return new TableColumn($field->getName(), $type, $length, null, null, $primaryKey, $autoIncrement);

    }

    /**
     * Map a field to an index column ready for use in creating a table index.
     * The main purpose of this function is to supply max bytes for string types
     *
     * @param Field $field
     * @return TableIndexColumn
     */
    public function mapFieldToIndexColumn($field) {

        $maxBytes = self::FIELD_TYPE_INDEX_MAX_BYTES_MAP[$field->getType()] ?? -1;
        return new TableIndexColumn($field->getName(), $maxBytes);

    }


    /**
     * Map a table column to a field
     *
     * @param ResultSetColumn $resultSetColumn
     * @return Field
     */
    public function mapResultSetColumnToField($resultSetColumn) {

        // Look up field type
        $fieldType = self::SQL_TYPE_FIELD_TYPE_MAP[$resultSetColumn->getType()] ?? Field::TYPE_LONG_STRING;

        // If an array, look up the sub type according to length
        if (is_array($fieldType)) {
            foreach ($fieldType as $length => $type) {
                if (($resultSetColumn->getLength() ?? 0) >= $length) {
                    $fieldType = $type;
                }
            }
        }

        $keyField = false;
        if ($resultSetColumn instanceof TableColumn) {
            // Handle special auto increment case
            if ($fieldType == Field::TYPE_INTEGER && $resultSetColumn->isAutoIncrement())
                $fieldType = Field::TYPE_ID;

            // Check for key field
            $keyField = $resultSetColumn->isPrimaryKey();
        }

        return new Field($resultSetColumn->getName(), null, null, $fieldType, $keyField);
    }

    /**
     * Returns the size of the memory taken up by the column in bytes.
     * @param string $columnType
     * @param int $columnLength
     * @return int
     * @throws Exception
     */
    public static function columnSize(string $columnType, ?int $columnLength = null) : int {
        return match ($columnType) {
            TableColumn::SQL_VARCHAR => 4*$columnLength+1,
            TableColumn::SQL_INT => 4,
            TableColumn::SQL_LONGBLOB => 25,
            TableColumn::SQL_DATE_TIME => 5,
            TableColumn::SQL_DATE => 3,
            TableColumn::SQL_INTEGER => 4,
            default => throw new NonexistentColumnTypeException(
                "$columnType isn't a SQL column type.")
        };
    }

}