<?php


namespace Kinintel\ValueObjects\Transformation\Columns;


use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Util\StringUtils;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

enum ColumnNamingConvention: string {
    case CAMEL = "camel";
    case UNDERSCORE = "underscore";
}

class ColumnsTransformation implements Transformation, SQLDatabaseTransformation {

    /**
     * @var Field[]
     */
    private $columns;

    /**
     * If set, column identifiers will be reset to match the supplied column titles supplied
     *
     * @var boolean
     */
    private $resetColumnNames = false;

    /**
     * If resetting, which naming convention to use for column names.
     *
     * @var ColumnNamingConvention
     */
    private $namingConvention = ColumnNamingConvention::CAMEL;


    /**
     * Columns constructor.
     *
     * @param Field[] $columns
     * @param boolean $resetColumnNames
     * @param ColumnNamingConvention $namingConvention
     */
    public function __construct($columns = [], $resetColumnNames = false, $namingConvention = ColumnNamingConvention::CAMEL) {
        $this->columns = Field::toPlainFields($columns);
        $this->resetColumnNames = $resetColumnNames;
        $this->namingConvention = $namingConvention;
    }


    /**
     * @return Field[]
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @param Field[] $columns
     */
    public function setColumns($columns) {
        $this->columns = $columns;
    }

    /**
     * @return bool
     */
    public function isResetColumnNames(): bool {
        return $this->resetColumnNames;
    }

    /**
     * @param bool $resetColumnNames
     */
    public function setResetColumnNames(bool $resetColumnNames): void {
        $this->resetColumnNames = $resetColumnNames;
    }

    /**
     * @return ColumnNamingConvention|null
     */
    public function getNamingConvention(): ?ColumnNamingConvention {
        return $this->namingConvention ?: ColumnNamingConvention::CAMEL;
    }

    /**
     * @param ColumnNamingConvention|null $namingConvention
     */
    public function setNamingConvention(?ColumnNamingConvention $namingConvention): void {
        $this->namingConvention = $namingConvention;
    }


    public function getSQLTransformationProcessorKey() {
        return "columns";
    }

    public function returnAlteredColumns(array $columns): array {
        $newColumnTitles = [];
        $existingColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $columns);
        foreach ($this->getColumns() as $newColumn) {
            $existingColumn = $existingColumns[$newColumn->getName()] ?? null;
            if (!$existingColumn) {
                $newColumns[] = $newColumn;
                continue;
            }
            // If resetting column names, create alias strings
            if ($this->isResetColumnNames()) {

                // Track number of occurrences of titles for suffix management
                $suffix = "";
                if (!isset($newColumnTitles[$newColumn->getTitle()]))
                    $newColumnTitles[$newColumn->getTitle()] = 1;
                else
                    $suffix = " " . ++$newColumnTitles[$newColumn->getTitle()];

                $newColumnName = $this->getNamingConvention() == ColumnNamingConvention::UNDERSCORE ?
                    StringUtils::convertToSnakeCase($newColumn->getTitle() . $suffix, true) :
                    StringUtils::convertToCamelCase($newColumn->getTitle() . $suffix, true);
            } else {
                $newColumnName = $newColumn->getName();
            }

            $newColumns[] = new Field($newColumnName, $newColumn->getTitle(), null,
                $existingColumn->getType(), $existingColumn->isKeyField());

        }

        return $newColumns;
    }
}